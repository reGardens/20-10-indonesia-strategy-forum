<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Api\Isf\Cms\FooterSettingController;
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
use App\Models\Isf\CaseEvent;
use App\Models\Isf\CaseTimeline;
use App\Models\Isf\Category;
use App\Models\Isf\Chart;
use App\Models\Isf\DatabasePeraturan;
use App\Models\Isf\Infographic;
use App\Models\Isf\LegalGlossary;
use App\Models\Isf\ParliamentSeat;
use App\Models\Isf\PolicyBrief;
use App\Models\Isf\PolicyTracker;
use App\Models\Isf\ReaderPoll;
use App\Models\Isf\ReaderPollVote;
use App\Models\Isf\PublicQuote;
use App\Models\Isf\Letter;
use App\Models\Isf\Survey;
use App\Models\Isf\TokohReview;
use App\Http\Requests\Isf\LetterSubmissionRequest;
use App\Http\Requests\Isf\PartnershipProposalRequest;
use App\Mail\PartnershipAutoReplyMail;
use App\Models\Isf\PartnershipProposal;
use App\Support\IsfRubrik;
use App\Models\Isf\Setting;
use App\Models\Isf\Tokoh;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            
            $runningArticles = Article::query()->terbit()
                ->where('show_in_running_text', true)
                ->orderByRaw('CASE WHEN running_text_order IS NULL THEN 99 ELSE running_text_order END ASC')
                ->orderByDesc('published_at')
                ->limit(5)
                ->get();

            if ($runningArticles->isNotEmpty()) {
                $activeTopBars = $runningArticles->values()->map(function (Article $a, int $idx) {
                    $pubDate = $a->published_at ? $a->published_at->format('d M Y') : 'Edisi Pekan Ini';
                    $url = $a->publicUrl();
                    return [
                        'id' => (string) $a->id,
                        'badge' => $a->categoryLabel(),
                        'announcement' => $a->title,
                        'info' => $pubDate,
                        'url' => $url,
                        'to' => $url,
                        'status' => 'terbit',
                        'order' => $a->running_text_order ?? ($idx + 1),
                    ];
                })->all();
            } else {
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
                $activeTopBars = array_values(array_filter($topBars, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'));
                if (empty($activeTopBars) && !empty($topBar['announcement'])) {
                    $activeTopBars = [$topBar];
                }
                $activeTopBars = array_map(function ($it) {
                    $targetUrl = $it['url'] ?? ($it['link'] ?? '');
                    $it['url'] = $targetUrl;
                    $it['to'] = $targetUrl;
                    return $it;
                }, $activeTopBars);
            }

            $banners = Banner::query()->terbit()
                ->orderBy('order', 'asc')
                ->orderByDesc('id')
                ->get()
                ->map(fn(Banner $b) => [
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
            $activeMedia = array_values(array_filter($mediaSorotan, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'));
            if (empty($activeMedia)) {
                $activeMedia = $this->defaultMediaItems();
            }
            usort($activeMedia, function ($a, $b) {
                $timeA = strtotime($a['published_at'] ?? ($a['publishedAt'] ?? '1970-01-01'));
                $timeB = strtotime($b['published_at'] ?? ($b['publishedAt'] ?? '1970-01-01'));
                if ($timeA === $timeB) {
                    return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
                }
                return $timeB <=> $timeA;
            });
            $activeMedia = array_map(function ($it) {
                $pub = $it['publishedAt'] ?? ($it['published_at'] ?? date('Y-m-d'));
                $it['publishedAt'] = $pub;
                $it['published_at'] = $pub;
                $it['to'] = '/media/' . ($it['id'] ?? 1);
                return $it;
            }, $activeMedia);

            $hotNews = $settings['hotNews'] ?? null;
            $activeHotNews = is_array($hotNews)
                ? array_values(array_filter($hotNews, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'))
                : [];

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
                        // Label pendek kategori (Politik / Hukum / Kebijakan) agar ticker tetap ringkas.
                $catLabel = $a->categoryKey() ? \App\Support\IsfRubrik::label($a->categoryKey()) : ($categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama'));
                        return [
                            'id' => (string) $a->id,
                            'title' => $a->title,
                            'category' => $catLabel,
                            'to' => $a->publicUrl(),
                            'url' => $a->publicUrl(),
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

            $topRankArticles = Article::query()->terbit()
                ->where('show_in_top_rank', true)
                ->orderByRaw('CASE WHEN top_rank_order IS NULL THEN 99 ELSE top_rank_order END ASC')
                ->orderByDesc('published_at')
                ->limit(3)
                ->get();

            if ($topRankArticles->isNotEmpty()) {
                $activeTopRank = $topRankArticles->values()->map(function (Article $a, int $idx) {
                    return [
                        'id' => (string) $a->id,
                        'rank' => (int) ($a->top_rank_order ?? ($idx + 1)),
                        'title' => $a->title,
                        'summary' => $a->summary ?: Str::limit(strip_tags((string) $a->body), 120),
                        'author' => $a->author_name ?: 'Redaksi ISF',
                        'image_url' => $a->image_url,
                        'to' => $a->publicUrl(),
                        'url' => $a->publicUrl(),
                        'published_at' => $a->published_at ? $a->published_at->format('d M Y') : date('d M Y'),
                        'status' => 'terbit',
                    ];
                })->all();
            } else {
                $topRankItems = $settings['topRank'] ?? [];
                $activeTopRank = is_array($topRankItems)
                    ? array_values(array_filter($topRankItems, fn($it) => ($it['status'] ?? 'terbit') === 'terbit'))
                    : [];

                if (empty($activeTopRank)) {
                    $activeTopRank = Article::query()->terbit()
                        ->orderByDesc('published_at')
                        ->orderByDesc('id')
                        ->limit(3)
                        ->get()
                        ->values()
                        ->map(function (Article $a, int $idx) {
                            return [
                                'id' => (string) $a->id,
                                'rank' => $idx + 1,
                                'title' => $a->title,
                                'summary' => $a->summary ?: Str::limit(strip_tags((string) $a->body), 120),
                                'author' => $a->author_name ?: 'Redaksi ISF',
                                'image_url' => $a->image_url,
                                'to' => $a->publicUrl(),
                                'url' => $a->publicUrl(),
                                'published_at' => $a->published_at ? $a->published_at->format('d M Y') : date('d M Y'),
                                'status' => 'terbit',
                            ];
                        })
                        ->all();
                } else {
                    usort($activeTopRank, fn($a, $b) => ((int) ($a['rank'] ?? 0)) <=> ((int) ($b['rank'] ?? 0)));
                    $activeTopRank = array_map(function ($it, $idx) {
                        $it['rank'] = (int) ($it['rank'] ?? ($idx + 1));
                        $it['to'] = $it['url'] ?? '/beranda';
                        return $it;
                    }, $activeTopRank, array_keys($activeTopRank));
                }
            }

            return [
                'topBar' => $settings['topBar'] ?? null,
                'topBars' => $activeTopBars,
                'banners' => $banners,
                'hotNews' => $activeHotNews,
                'topRank' => $activeTopRank,
                'hero' => $hero ? (new ArticleResource($hero))->hero() : null,
                'factBlocks' => $hero?->fact_blocks ?? [],
                'chart' => $chart ? (new ChartResource($chart))->resolve($request) : null,
                'charts' => ChartResource::collection($charts)->resolve($request),
                'mediaSorotan' => $activeMedia,
                'terkini' => ArticleResource::collection($terkini)->resolve($request),
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
     * GET /api/isf/infografik/{slug}
     *
     * Detail infografik publik (hanya status terbit) + infografik lain
     * (kategori sama didahulukan, dilengkapi yang terbaru).
     */
    public function infografikDetail(Request $request, string $slug): JsonResponse
    {
        $item = Infographic::query()->terbit()->where('slug', $slug)->first();

        if (!$item) {
            return $this->notFound('Infografik tidak ditemukan.');
        }

        $sameCategory = $item->category
            ? Infographic::query()->terbit()
                ->where('category', $item->category)
                ->whereKeyNot($item->getKey())
                ->orderByDesc('published_at')->orderByDesc('id')
                ->take(4)->get()
            : collect();

        $others = $sameCategory;
        if ($others->count() < 4) {
            $fill = Infographic::query()->terbit()
                ->whereKeyNot($item->getKey())
                ->whereNotIn('id', $sameCategory->modelKeys())
                ->orderByDesc('published_at')->orderByDesc('id')
                ->take(4 - $others->count())->get();
            $others = $others->concat($fill);
        }

        return response()->json([
            'data' => (new InfographicResource($item))->resolve($request),
            'related' => InfographicResource::collection($others->values())->resolve($request),
        ]);
    }

    /**
     * GET /api/isf/tokoh/{slug}
     */
    public function tokoh(Request $request, string $slug): JsonResponse
    {
        $tokoh = Tokoh::query()->terbit()->where('slug', $slug)->first();

        if (!$tokoh) {
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
                    'name' => '',
                    'role' => '',
                    'organization' => '',
                    'plan' => '',
                    'sektor' => [],
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
        $activeMedia = array_values(array_filter($mediaSorotan, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'));

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

        $related = array_values(array_filter($activeMedia, fn($it) => (string) ($it['id'] ?? '') !== (string) ($item['id'] ?? '')));
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
     * GET /api/isf/media-sorotan
     * Menyajikan daftar item sorotan terurut berdasarkan berita terbaru (published_at desc) dengan status terbit.
     */
    public function mediaSorotan(Request $request): JsonResponse
    {
        $settings = Setting::get('beranda', []);
        $mediaSorotan = $settings['mediaSorotan'] ?? [];
        $activeMedia = array_values(array_filter($mediaSorotan, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'));

        if (empty($activeMedia)) {
            $activeMedia = $this->defaultMediaItems();
        }

        usort($activeMedia, function ($a, $b) {
            $timeA = strtotime($a['published_at'] ?? ($a['publishedAt'] ?? '1970-01-01'));
            $timeB = strtotime($b['published_at'] ?? ($b['publishedAt'] ?? '1970-01-01'));
            if ($timeA === $timeB) {
                return ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
            }
            return $timeB <=> $timeA;
        });

        $activeMedia = array_map(function ($it) {
            $pub = $it['publishedAt'] ?? ($it['published_at'] ?? date('Y-m-d'));
            $it['publishedAt'] = $pub;
            $it['published_at'] = $pub;
            $it['to'] = '/media/' . ($it['id'] ?? 1);
            return $it;
        }, $activeMedia);

        return response()->json([
            'data' => $activeMedia,
            'total' => count($activeMedia),
        ]);
    }

    /**
     * GET /api/isf/artikel/{slug}
     *
     * Detail artikel publik (hanya status terbit) beserta artikel terkait
     * (kategori sama) dan artikel terbaru untuk sidebar.
     */
    public function artikel(Request $request, string $slug): JsonResponse
    {
        $article = Article::query()->terbit()->where('slug', $slug)->first();

        if (!$article) {
            return $this->notFound('Artikel tidak ditemukan.');
        }

        $categoryLabel = $this->categoryLabeler();
        $card = $this->articleCardMapper($categoryLabel);

        // Terkait: artikel lain dalam kategori yang sama.
        $categoryKey = $article->categoryKey();
        $relatedCategories = $categoryKey ? IsfRubrik::categories($categoryKey) : array_filter([$article->category]);

        $related = $relatedCategories
            ? Article::query()->terbit()
                ->whereIn('category', $relatedCategories)
                ->whereKeyNot($article->getKey())
                ->orderByDesc('published_at')->orderByDesc('id')
                ->take(3)->get()
            : collect();

        $recent = Article::query()->terbit()
            ->whereKeyNot($article->getKey())
            ->orderByDesc('published_at')->orderByDesc('id')
            ->take(5)->get();

        $factBlocks = collect(is_array($article->fact_blocks) ? $article->fact_blocks : [])
            ->filter(fn($b) => is_array($b) && trim((string) ($b['claim'] ?? '')) !== '')
            ->map(fn($b) => [
                'claim' => (string) $b['claim'],
                'sourceUrl' => (string) ($b['sourceUrl'] ?? ''),
                'sourceTitle' => (string) ($b['sourceTitle'] ?? ''),
                'sourceDate' => (string) ($b['sourceDate'] ?? ''),
            ])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'slug' => $article->slug,
                'title' => $article->title,
                'category' => $categoryKey ? IsfRubrik::label($categoryKey) : $categoryLabel($article->category),
                'categorySlug' => $categoryKey ?? $article->category,
                // URL kanonis /artikel/{kategori}/{slug} + kategori untuk breadcrumb (→ /artikel/{kategori}).
                'path' => $article->detailPath(),
                'kategori' => $categoryKey ? IsfRubrik::summary($categoryKey) : null,
                'summary' => $article->summary ?? '',
                'body' => $article->body ?? '',
                'publishedAt' => $article->published_at?->format('Y-m-d'),
                'readingMinutes' => $article->reading_minutes,
                'imageUrl' => $article->image_url,
                'imageAlt' => $article->image_alt ?: $article->title,
                'author' => [
                    'name' => $article->author_name ?: 'Tim Riset ISF',
                    'role' => $article->author_role ?? '',
                ],
                'factBlocks' => $factBlocks,
            ],
            'related' => $related->map($card)->values()->all(),
            'recentArticles' => $recent->map($card)->values()->all(),
        ]);
    }

    /**
     * GET /api/isf/kategori/{kategori}?offset=N&limit=N
     *
     * Daftar artikel terbit satu kategori (terbaru dulu) untuk halaman /artikel/{kategori}.
     * Default 7 = 1 Sorotan Utama + 6 Artikel Terbaru; "Lihat lainnya" memuat 6 berikutnya (offset).
     */
    public function kategori(Request $request, string $kategori): JsonResponse
    {
        if (!IsfRubrik::exists($kategori)) {
            return $this->notFound('Kategori tidak ditemukan.');
        }

        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(24, max(1, (int) $request->input('limit', 7)));

        $query = Article::query()->terbit()->whereIn('category', IsfRubrik::categories($kategori));
        $total = (clone $query)->count();
        $items = $query->orderByDesc('published_at')->orderByDesc('id')
            ->skip($offset)->take($limit)->get();

        $card = $this->articleCardMapper($this->categoryLabeler());
        $next = $offset + $items->count();

        return response()->json([
            'kategori' => IsfRubrik::summary($kategori),
            'data' => $items->map($card)->values()->all(),
            'meta' => [
                'offset' => $offset,
                'total' => $total,
                'nextOffset' => $next < $total ? $next : null,
            ],
        ]);
    }

    /**
     * Label kategori artikel (nama Kategori CMS bila ada).
     */
    private function categoryLabeler(): \Closure
    {
        $categoriesBySlug = Category::query()->terbit()->pluck('name', 'slug')->all();

        return function (?string $category) use ($categoriesBySlug): string {
            $key = strtolower(trim((string) $category));
            if ($key === '') {
                return 'Analisis';
            }

            return $categoriesBySlug[$key] ?? ucfirst($key);
        };
    }

    /**
     * Bentuk kartu artikel publik (detail terkait, sidebar, rubrik).
     */
    private function articleCardMapper(\Closure $categoryLabel): \Closure
    {
        return fn(Article $a) => [
            'slug' => $a->slug,
            'title' => $a->title,
            'category' => $a->categoryKey() ? IsfRubrik::label($a->categoryKey()) : $categoryLabel($a->category),
            'subcategory' => $a->subcategory,
            'summary' => $a->summary ?? '',
            'publishedAt' => $a->published_at?->format('Y-m-d'),
            'readingMinutes' => $a->reading_minutes,
            'imageUrl' => $a->image_url,
            'imageAlt' => $a->image_alt ?: $a->title,
            'author' => $a->author_name ?: 'Tim Riset ISF',
            'to' => $a->publicUrl(),
        ];
    }

    /**
     * GET /api/isf/politik
     *
     * Halaman Rubrik Politik:
     * - Daftar artikel politik terbit dengan headline utama
     * - Sub-kategori: Pemilu & Pilkada, DPR/DPD/MPR, Eksekutif, Dinamika Partai
     * - Pagination: page, limit, total, lastPage
     * - Infografis interaktif: Peta Kursi Parlemen
     * - Profil Tokoh Politik: kartu biodata tokoh terpilih (show_in_politik = true)
     */
    public function politik(Request $request): JsonResponse
    {
        $subkategori = $request->query('subkategori') ? strtolower(trim((string) $request->query('subkategori'))) : null;
        $search = $request->query('q') ? trim((string) $request->query('q')) : null;
        $date = $request->query('date') ? trim((string) $request->query('date')) : null;
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(48, max(1, (int) $request->input('limit', 12)));

        $politikCat = Category::query()->terbit()->where('slug', 'politik')->first();

        // Daftar subkategori lengkap sesuai kebutuhan rubrik Politik ISF
        $subcategories = [
            ['name' => 'Sorotan', 'slug' => 'sorotan', 'description' => 'Isu dan laporan khusus sorotan politik nasional.'],
            ['name' => 'Eksekutif', 'slug' => 'eksekutif', 'description' => 'Kebijakan kementerian, kabinet, dan lembaga eksekutif.'],
            ['name' => 'Legislatif', 'slug' => 'legislatif', 'description' => 'Kinerja legislasi, rapat komisi, dan dinamika parlemen DPR RI.'],
            ['name' => 'Dinamika Partai', 'slug' => 'dinamika-partai', 'description' => 'Perkembangan internal, suksesi, dan peta koalisi partai politik.'],
            ['name' => 'Pemilu Pilkada', 'slug' => 'pemilu-pilkada', 'description' => 'Isu dinamika Pemilu, Pilpres, Pilkada, dan kepemiluan.'],
            ['name' => 'Kegiatan Istana', 'slug' => 'kegiatan-istana', 'description' => 'Agenda, arahan strategis, dan kegiatan kepresidenan.'],
        ];

        // Query artikel politik
        $query = Article::query()->terbit()->where(function ($q) {
            $q->where('category', 'politik')
                ->orWhere('category', 'like', '%politik%');
        });

        if ($subkategori && !in_array($subkategori, ['all', 'semua'])) {
            $subQuery = (clone $query)->where(function ($q) use ($subkategori) {
                if ($subkategori === 'sorotan') {
                    $q->where('is_hero', true)
                        ->orWhere('subcategory', 'like', '%sorotan%');
                } elseif ($subkategori === 'eksekutif' || $subkategori === 'pemerintahan') {
                    $q->whereIn('subcategory', ['eksekutif', 'pemerintahan'])
                        ->orWhere('category', 'eksekutif');
                } elseif ($subkategori === 'legislatif' || $subkategori === 'parlemen') {
                    $q->whereIn('subcategory', ['legislatif', 'parlemen', 'dpr', 'dpd', 'mpr'])
                        ->orWhere('category', 'legislatif');
                } elseif ($subkategori === 'dinamika-partai' || $subkategori === 'partai') {
                    $q->whereIn('subcategory', ['dinamika-partai', 'partai'])
                        ->orWhere('subcategory', 'like', '%partai%');
                } elseif ($subkategori === 'pemilu-pilkada' || $subkategori === 'pemilu') {
                    $q->whereIn('subcategory', ['pemilu-pilkada', 'pemilu'])
                        ->orWhere('subcategory', 'like', '%pemilu%');
                } elseif ($subkategori === 'kegiatan-istana' || $subkategori === 'istana') {
                    $q->whereIn('subcategory', ['kegiatan-istana', 'istana'])
                        ->orWhere('subcategory', 'like', '%istana%');
                } else {
                    $q->where('subcategory', $subkategori)
                        ->orWhere('category', $subkategori);
                }
            });

            // Terapkan jika artikel dengan subkategori tersebut ada; jika belum ada, fallback ke artikel politik umum agar tidak kosong
            if ($subQuery->count() > 0) {
                $query = $subQuery;
            }
        }

        // Filter full date (YYYY-MM-DD)
        if ($date) {
            $query->whereDate('published_at', $date);
        }

        // Filter search pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $articles = $query->orderByDesc('published_at')->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();

        $categoryLabel = $this->categoryLabeler();
        $cardMapper = $this->articleCardMapper($categoryLabel);
        $mappedArticles = $articles->map($cardMapper)->values()->all();

        // Headline: pada halaman 1, artikel pertama dijadikan headline
        $headline = null;
        if ($page === 1 && count($mappedArticles) > 0) {
            $headline = $mappedArticles[0];
        }
        $listArticles = $mappedArticles;

        // Peta Kursi Parlemen default (DPR 2024-2029)
        $seatRows = ParliamentSeat::query()
            ->terbit()
            ->where('periode', '2024-2029')
            ->where('lembaga', 'DPR')
            ->orderBy('urutan', 'asc')
            ->orderByDesc('jumlah_kursi')
            ->get();

        $totalKursi = (int) $seatRows->sum('jumlah_kursi');
        $seatItems = $seatRows->map(function (ParliamentSeat $s) use ($totalKursi) {
            $pct = $totalKursi > 0 ? round(($s->jumlah_kursi / $totalKursi) * 100, 2) : 0;
            return [
                'id' => $s->id,
                'partai' => $s->partai,
                'singkatan' => $s->singkatan ?: $s->partai,
                'jumlah_kursi' => (int) $s->jumlah_kursi,
                'persentase' => $pct,
                'warna_partai' => $s->warna_partai,
                'urutan' => $s->urutan,
            ];
        })->values()->all();

        // Tokoh Politik: Tampilkan tokoh yang diaktifkan show_in_politik atau tokoh terbit lainnya (hingga 4 kartu profil)
        $tokohPolitik = Tokoh::query()
            ->terbit()
            ->orderByDesc('show_in_politik')
            ->orderByDesc('updated_at')
            ->take(4)
            ->get()
            ->map(function (Tokoh $t) {
                return [
                    'id' => $t->id,
                    'slug' => $t->slug,
                    'name' => $t->name,
                    'position' => $t->position,
                    'photoUrl' => $t->photo_url,
                    'photoAlt' => $t->photo_alt ?: $t->name,
                    'teaser' => $t->teaser ?: "Profil dan rekam jejak kepemimpinan serta analisis kebijakan {$t->name}.",
                    'stats' => $t->stats ?: [],
                    'to' => "/sosok/{$t->slug}",
                ];
            })->values()->all();

        return response()->json([
            'rubrik' => [
                'key' => 'politik',
                'title' => 'Politik',
                'label' => 'Politik',
                'description' => $politikCat?->description ?: 'Dinamika politik nasional, parlemen, partai, pemilu, dan lembaga negara.',
                'subkategori' => $subcategories,
                'activeSubkategori' => $subkategori,
            ],
            'headline' => $headline,
            'articles' => $listArticles,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
            'petaKursi' => [
                'periode' => '2024-2029',
                'lembaga' => 'DPR',
                'totalKursi' => $totalKursi,
                'items' => $seatItems,
            ],
            'tokohPolitik' => $tokohPolitik,
        ]);
    }

    /**
     * GET /api/isf/peta-kursi
     *
     * Data interaktif komposisi kursi parlemen (DPR / DPD / MPR) per periode.
     */
    public function petaKursi(Request $request): JsonResponse
    {
        $lembaga = strtoupper(trim((string) $request->input('lembaga', 'DPR')));
        if (!in_array($lembaga, ['DPR', 'DPD', 'MPR'])) {
            $lembaga = 'DPR';
        }
        $periode = trim((string) $request->input('periode', '2024-2029'));

        $seats = ParliamentSeat::query()
            ->terbit()
            ->where('lembaga', $lembaga)
            ->where('periode', $periode)
            ->orderBy('urutan', 'asc')
            ->orderByDesc('jumlah_kursi')
            ->get();

        $totalKursi = (int) $seats->sum('jumlah_kursi');
        $items = $seats->map(function (ParliamentSeat $s) use ($totalKursi) {
            $pct = $totalKursi > 0 ? round(($s->jumlah_kursi / $totalKursi) * 100, 2) : 0;
            return [
                'id' => $s->id,
                'partai' => $s->partai,
                'singkatan' => $s->singkatan ?: $s->partai,
                'jumlah_kursi' => (int) $s->jumlah_kursi,
                'persentase' => $pct,
                'warna_partai' => $s->warna_partai,
                'urutan' => $s->urutan,
            ];
        })->values()->all();

        return response()->json([
            'lembaga' => $lembaga,
            'periode' => $periode,
            'totalKursi' => $totalKursi,
            'items' => $items,
        ]);
    }

    /**
     * GET /api/isf/hukum
     *
     * Halaman Rubrik Hukum:
     * - Kategori induk Hukum & sub-kategori (/hukum/regulasi, /hukum/putusan, /hukum/kasus)
     * - Headline utama hukum (artikel hero hukum atau artikel terbaru)
     * - Opini Pakar Hukum (artikel hukum dengan is_expert_opinion = true)
     * - Timeline Kasus Hukum Besar (alur waktu visual beserta peristiwanya)
     * - Glosarium Hukum (kamus istilah interaktif A-Z)
     * - Arsip artikel hukum terpaginasi (mendukung filter subkategori)
     */
    public function hukum(Request $request): JsonResponse
    {
        $subkategori = $request->query('subkategori') ? strtolower(trim((string) $request->query('subkategori'))) : null;
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(48, max(1, (int) $request->input('limit', 12)));

        // Kategori induk Hukum
        $hukumCat = Category::query()->terbit()->where('slug', 'hukum')->first();
        $subcategories = [];
        if ($hukumCat) {
            $subcategories = Category::query()
                ->terbit()
                ->where('parent_id', $hukumCat->id)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'name', 'slug', 'description', 'color'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                    'color' => $c->color,
                ])
                ->values()
                ->all();
        }

        if (empty($subcategories)) {
            $subcategories = [
                ['name' => 'Regulasi & Perundang-undangan', 'slug' => 'regulasi', 'description' => 'UU, Perpres, Perda baru, dan analisis kepatuhan regulasi.'],
                ['name' => 'Putusan Peradilan', 'slug' => 'putusan', 'description' => 'Analisis Putusan MK, MA, dan peradilan umum.'],
                ['name' => 'Kasus Hukum', 'slug' => 'kasus', 'description' => 'Kasus hukum besar, tipikor, dan proses peradilan.'],
            ];
        }

        // Query artikel kategori hukum (termasuk legacy 'ruu')
        $query = Article::query()->terbit()->whereIn('category', ['hukum', 'ruu']);

        if ($subkategori && $subkategori !== 'semua' && $subkategori !== 'glosarium') {
            $subQuery = (clone $query)->where(function ($q) use ($subkategori) {
                $q->where('subcategory', $subkategori)
                    ->orWhere('category', $subkategori);
            });
            // Jika ada artikel dengan subkategori tersebut, terapkan filternya.
            // Jika subkategori belum memiliki artikel khusus, fallback menampilkan artikel hukum terbaru agar tidak kosong.
            if ($subQuery->count() > 0) {
                $query = $subQuery;
            }
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $articles = $query->orderByDesc('published_at')->orderByDesc('id')
            ->forPage($page, $limit)
            ->get();

        $categoryLabel = $this->categoryLabeler();
        $cardMapper = $this->articleCardMapper($categoryLabel);
        $mappedArticles = $articles->map($cardMapper)->values()->all();

        // Headline: pada halaman 1, artikel pertama dijadikan headline
        $headline = null;
        if ($page === 1 && count($mappedArticles) > 0) {
            $headline = $mappedArticles[0];
        }
        $listArticles = $mappedArticles;

        // Opini Pakar Hukum
        $opiniPakar = Article::query()
            ->terbit()
            ->whereIn('category', ['hukum', 'ruu'])
            ->where('is_expert_opinion', true)
            ->orderByDesc('published_at')
            ->take(4)
            ->get()
            ->map(function (Article $a) {
                return [
                    'id' => $a->id,
                    'slug' => $a->slug,
                    'title' => $a->title,
                    'summary' => $a->summary,
                    'expert_name' => $a->expert_name ?: ($a->author_name ?: 'Pakar Hukum'),
                    'expert_title' => $a->expert_title ?: ($a->author_role ?: 'Akademisi & Praktisi Hukum'),
                    'expert_avatar' => $a->expert_avatar ?: $a->image_url,
                    'publishedAt' => $a->published_at?->format('Y-m-d'),
                    'readingMinutes' => $a->reading_minutes,
                    'to' => $a->publicUrl(),
                ];
            })->values()->all();

        // Timeline Kasus Hukum
        $timelines = CaseTimeline::query()
            ->terbit()
            ->with(['events' => function ($q) {
                $q->terbit()->orderBy('tanggal_peristiwa', 'asc')->orderBy('urutan', 'asc');
            }])
            ->orderBy('urutan', 'asc')
            ->orderByDesc('id')
            ->take(5)
            ->get()
            ->map(function (CaseTimeline $t) {
                return [
                    'id' => $t->id,
                    'judul_kasus' => $t->judul_kasus,
                    'slug' => $t->slug,
                    'kategori_kasus' => $t->kategori_kasus,
                    'deskripsi' => $t->deskripsi,
                    'status_kasus' => $t->status_kasus,
                    'gambar' => $t->gambar,
                    'events' => $t->events->map(fn (CaseEvent $e) => [
                        'id' => $e->id,
                        'tanggal' => $e->tanggal_peristiwa?->format('Y-m-d'),
                        'tanggal_formatted' => $e->tanggal_peristiwa ? \Carbon\Carbon::parse($e->tanggal_peristiwa)->locale('id')->isoFormat('D MMMM Y') : '',
                        'tahap' => $e->tahap,
                        'judul' => $e->judul,
                        'deskripsi' => $e->deskripsi,
                        'sumber_tautan' => $e->sumber_tautan,
                    ])->values()->all(),
                ];
            })->values()->all();

        // Glosarium Hukum istilah terbit
        $glossaryQuery = LegalGlossary::query()->terbit()->orderBy('abjad', 'asc')->orderBy('istilah', 'asc');
        $glossaryTerms = $glossaryQuery->get()->map(fn (LegalGlossary $g) => [
            'id' => $g->id,
            'istilah' => $g->istilah,
            'slug' => $g->slug,
            'abjad' => $g->abjad,
            'definisi' => $g->definisi,
            'dasar_hukum' => $g->dasar_hukum,
            'istilah_terkait' => $g->istilah_terkait ?: [],
        ])->values()->all();

        return response()->json([
            'rubrik' => [
                'key' => 'hukum',
                'title' => 'Hukum',
                'label' => 'Hukum',
                'description' => $hukumCat?->description ?: 'Analisis yuridis, RUU, putusan peradilan, penegakan hukum, dan kepatuhan regulasi.',
                'subkategori' => $subcategories,
                'activeSubkategori' => $subkategori,
            ],
            'headline' => $headline,
            'articles' => $listArticles,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
            'opiniPakar' => $opiniPakar,
            'timelineKasus' => $timelines,
            'glosarium' => $glossaryTerms,
        ]);
    }

    /**
     * GET /api/isf/glosarium-hukum
     *
     * Kamus istilah hukum interaktif dengan pencarian dan filter huruf A-Z.
     */
    public function glosariumHukum(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $letter = strtoupper(trim((string) $request->input('letter', '')));

        $query = LegalGlossary::query()->terbit();

        if ($letter !== '' && strlen($letter) === 1) {
            $query->where('abjad', $letter);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('istilah', 'like', "%{$q}%")
                    ->orWhere('definisi', 'like', "%{$q}%")
                    ->orWhere('dasar_hukum', 'like', "%{$q}%");
            });
            $query->orderByRaw("CASE WHEN istilah LIKE ? THEN 0 ELSE 1 END", ["%{$q}%"]);
        }

        $items = $query->orderBy('abjad', 'asc')
            ->orderBy('istilah', 'asc')
            ->get()
            ->map(fn (LegalGlossary $g) => [
                'id' => $g->id,
                'istilah' => $g->istilah,
                'slug' => $g->slug,
                'abjad' => $g->abjad,
                'definisi' => $g->definisi,
                'dasar_hukum' => $g->dasar_hukum,
                'istilah_terkait' => $g->istilah_terkait ?: [],
            ])->values()->all();

        // Hitung ketersediaan istilah per abjad A-Z
        $allCounts = LegalGlossary::query()->terbit()
            ->selectRaw('abjad, count(*) as total')
            ->groupBy('abjad')
            ->pluck('total', 'abjad')
            ->all();

        return response()->json([
            'query' => $q,
            'letter' => $letter,
            'total' => count($items),
            'availableLetters' => $allCounts,
            'items' => $items,
        ]);
    }

    /**
     * GET /api/isf/timeline-kasus
     *
     * Alur kronologis kasus hukum besar & peristiwa.
     */
    public function timelineKasus(Request $request): JsonResponse
    {
        $slug = trim((string) $request->input('slug', ''));

        $query = CaseTimeline::query()->terbit()
            ->with(['events' => function ($q) {
                $q->terbit()->orderBy('tanggal_peristiwa', 'asc')->orderBy('urutan', 'asc');
            }]);

        if ($slug !== '') {
            $query->where('slug', $slug);
        }

        $timelines = $query->orderBy('urutan', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CaseTimeline $t) => [
                'id' => $t->id,
                'judul_kasus' => $t->judul_kasus,
                'slug' => $t->slug,
                'kategori_kasus' => $t->kategori_kasus,
                'deskripsi' => $t->deskripsi,
                'status_kasus' => $t->status_kasus,
                'gambar' => $t->gambar,
                'events' => $t->events->map(fn (CaseEvent $e) => [
                    'id' => $e->id,
                    'tanggal' => $e->tanggal_peristiwa?->format('Y-m-d'),
                    'tanggal_formatted' => $e->tanggal_peristiwa ? \Carbon\Carbon::parse($e->tanggal_peristiwa)->locale('id')->isoFormat('D MMMM Y') : '',
                    'tahap' => $e->tahap,
                    'judul' => $e->judul,
                    'deskripsi' => $e->deskripsi,
                    'sumber_tautan' => $e->sumber_tautan,
                ])->values()->all(),
            ])->values()->all();

        return response()->json([
            'data' => $timelines,
        ]);
    }

    /**
     * GET /api/isf/kebijakan-publik
     *
     * Halaman Rubrik Kebijakan Publik:
     * - Kategori induk Kebijakan Publik & sub-kategori sektoral (ekonomi, kesehatan, pendidikan, pertahanan, lingkungan)
     * - Headline utama kebijakan publik & arsip artikel terpaginasi
     * - Policy Brief (ringkasan eksekutif riset & dokumen PDF yang bisa diunduh)
     * - Data & Statistik (grafik interaktif APBN, BPS, dll.)
     * - Monitoring Kebijakan (pelacakan program pemerintah: Janji vs Realisasi)
     */
    public function kebijakanPublik(Request $request): JsonResponse
    {
        $subkategori = $request->query('subkategori') ? strtolower(trim((string) $request->query('subkategori'))) : null;
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(48, max(1, (int) $request->input('limit', 12)));

        // Kategori induk Kebijakan Publik
        $kebijakanCat = Category::query()->terbit()->where('slug', 'kebijakan')->first();
        $subcategories = [];
        if ($kebijakanCat) {
            $subcategories = Category::query()
                ->terbit()
                ->where('parent_id', $kebijakanCat->id)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'name', 'slug', 'description', 'color'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                    'color' => $c->color,
                ])
                ->values()
                ->all();
        }

        if (empty($subcategories)) {
            $subcategories = [
                ['name' => 'Ekonomi & Fiskal', 'slug' => 'ekonomi', 'description' => 'Kebijakan fiskal, APBN, moneter, inflasi, dan stabilitas makroekonomi.'],
                ['name' => 'Kesehatan Masyarakat', 'slug' => 'kesehatan', 'description' => 'Sistem kesehatan nasional, BPJS, ketahanan farmasi, dan layanan medis.'],
                ['name' => 'Pendidikan & Riset', 'slug' => 'pendidikan', 'description' => 'Kurikulum, alokasi anggaran pendidikan, riset teknologi, dan SDM.'],
                ['name' => 'Pertahanan & Keamanan', 'slug' => 'pertahanan', 'description' => 'Modernisasi alutsista, ketahanan siber, diplomasi pertahanan, dan perbatasan.'],
                ['name' => 'Lingkungan & Energi', 'slug' => 'lingkungan', 'description' => 'Transisi energi terbarukan, dekarbonisasi industri, kehutanan, dan tata kelola iklim.'],
            ];
        }

        // Query artikel kategori kebijakan (termasuk legacy 'ekonomi')
        $query = Article::query()
            ->terbit()
            ->whereIn('category', ['kebijakan', 'ekonomi'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($subkategori && in_array($subkategori, ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'], true)) {
            $subQuery = (clone $query)->where(function ($q) use ($subkategori) {
                $q->where('subcategory', $subkategori)
                    ->orWhere('category', $subkategori);
            });
            // Jika ada artikel dengan subkategori tersebut, terapkan filternya.
            // Jika belum ada artikel khusus, fallback menampilkan artikel terbaru kebijakan agar filter tidak kosong.
            if ($subQuery->count() > 0) {
                $query = $subQuery;
            }
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $articles = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $categoryLabel = $this->categoryLabeler();
        $cardMapper = $this->articleCardMapper($categoryLabel);
        $mappedArticles = $articles->map($cardMapper)->values()->all();

        // Headline
        $headline = null;
        if ($page === 1 && count($mappedArticles) > 0) {
            $headline = $mappedArticles[0];
        }
        $listArticles = $mappedArticles;

        // Policy Briefs
        $briefQuery = PolicyBrief::query()->terbit();
        if ($subkategori && in_array($subkategori, ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'], true)) {
            $briefQuery->where('sector', $subkategori);
        }
        $policyBriefs = $briefQuery->orderBy('order', 'asc')
            ->orderByDesc('published_at')
            ->take(6)
            ->get()
            ->map(fn (PolicyBrief $b) => [
                'id' => $b->id,
                'title' => $b->title,
                'slug' => $b->slug,
                'sector' => $b->sector,
                'author' => $b->author,
                'institution' => $b->institution,
                'executive_summary' => $b->executive_summary,
                'key_recommendations' => $b->key_recommendations ?: [],
                'pdf_path' => $b->pdf_path,
                'pdf_size' => $b->pdf_size,
                'published_at' => $b->published_at?->format('Y-m-d'),
                'published_at_formatted' => $b->published_at ? \Carbon\Carbon::parse($b->published_at)->locale('id')->isoFormat('D MMMM Y') : '',
            ])->values()->all();

        // Data & Statistik (Charts)
        $chartQuery = Chart::query()->terbit();
        if ($subkategori && in_array($subkategori, ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'], true)) {
            $chartQuery->where(function ($q) use ($subkategori) {
                $q->where('sector', $subkategori)->orWhereNull('sector');
            });
        }
        $dataGrafik = $chartQuery->orderBy('order', 'asc')
            ->orderByDesc('id')
            ->take(5)
            ->get()
            ->map(fn (Chart $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description,
                'unit' => $c->unit,
                'sector' => $c->sector,
                'data' => is_array($c->data) ? $c->data : json_decode($c->data, true),
                'source_title' => $c->source_title,
                'source_url' => $c->source_url,
                'author_name' => $c->author_name,
                'author_role' => $c->author_role,
            ])->values()->all();

        // Monitoring Kebijakan (Policy Trackers)
        $trackerQuery = PolicyTracker::query()->terbit();
        if ($subkategori && in_array($subkategori, ['ekonomi', 'kesehatan', 'pendidikan', 'pertahanan', 'lingkungan'], true)) {
            $trackerQuery->where('sector', $subkategori);
        }
        $monitoringKebijakan = $trackerQuery->orderBy('order', 'asc')
            ->orderByDesc('last_updated_date')
            ->take(8)
            ->get()
            ->map(fn (PolicyTracker $t) => [
                'id' => $t->id,
                'program_name' => $t->program_name,
                'slug' => $t->slug,
                'sector' => $t->sector,
                'promise' => $t->promise,
                'realization' => $t->realization,
                'progress_percent' => $t->progress_percent,
                'status_label' => $t->status_label,
                'source_title' => $t->source_title,
                'source_url' => $t->source_url,
                'last_updated_date' => $t->last_updated_date?->format('Y-m-d'),
                'last_updated_formatted' => $t->last_updated_date ? \Carbon\Carbon::parse($t->last_updated_date)->locale('id')->isoFormat('D MMMM Y') : '',
            ])->values()->all();

        // Database Peraturan & Rekomendasi Kebijakan Dinamis
        $databasePeraturans = DatabasePeraturan::query()->terbit()
            ->orderBy('urutan', 'asc')
            ->orderByDesc('id')
            ->take(12)
            ->get()
            ->map(fn (DatabasePeraturan $dp) => [
                'id' => $dp->id,
                'judul' => $dp->judul,
                'nomor' => $dp->nomor,
                'tahun' => $dp->tahun,
                'kategori' => $dp->kategori,
                'tentang' => $dp->tentang,
                'url_dokumen' => $dp->url_dokumen,
                'lembaga' => $dp->lembaga,
            ])->values()->all();

        return response()->json([
            'rubrik' => [
                'key' => 'kebijakan',
                'title' => 'Kebijakan Publik',
                'label' => 'Kebijakan Publik',
                'description' => $kebijakanCat?->description ?: 'Kebijakan publik, ekonomi, fiskal, moneter, dan regulasi sektoral.',
                'subkategori' => $subcategories,
                'activeSubkategori' => $subkategori,
            ],
            'headline' => $headline,
            'articles' => $listArticles,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
            'policyBriefs' => $policyBriefs,
            'dataGrafik' => $dataGrafik,
            'monitoringKebijakan' => $monitoringKebijakan,
            'databasePeraturans' => $databasePeraturans,
        ]);
    }

    /**
     * GET /api/isf/policy-brief
     *
     * Daftar ringkasan eksekutif policy brief dengan tautan unduh PDF.
     */
    public function policyBrief(Request $request): JsonResponse
    {
        $sector = $request->query('sector') ? strtolower(trim((string) $request->query('sector'))) : null;
        $q = trim((string) $request->input('q', ''));

        $query = PolicyBrief::query()->terbit();

        if ($sector) {
            $query->where('sector', $sector);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('executive_summary', 'like', "%{$q}%");
            });
        }

        $briefs = $query->orderBy('order', 'asc')
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (PolicyBrief $b) => [
                'id' => $b->id,
                'title' => $b->title,
                'slug' => $b->slug,
                'sector' => $b->sector,
                'author' => $b->author,
                'institution' => $b->institution,
                'executive_summary' => $b->executive_summary,
                'key_recommendations' => $b->key_recommendations ?: [],
                'pdf_path' => $b->pdf_path,
                'pdf_size' => $b->pdf_size,
                'published_at' => $b->published_at?->format('Y-m-d'),
                'published_at_formatted' => $b->published_at ? \Carbon\Carbon::parse($b->published_at)->locale('id')->isoFormat('D MMMM Y') : '',
            ])->values()->all();

        return response()->json([
            'data' => $briefs,
        ]);
    }

    /**
     * GET /api/isf/monitoring-kebijakan
     *
     * Pelacakan program pemerintah: Janji vs Realisasi.
     */
    public function monitoringKebijakan(Request $request): JsonResponse
    {
        $sector = $request->query('sector') ? strtolower(trim((string) $request->query('sector'))) : null;
        $statusLabel = $request->query('status_label') ? strtolower(trim((string) $request->query('status_label'))) : null;
        $q = trim((string) $request->input('q', ''));

        $query = PolicyTracker::query()->terbit();

        if ($sector) {
            $query->where('sector', $sector);
        }

        if ($statusLabel) {
            $query->where('status_label', $statusLabel);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('program_name', 'like', "%{$q}%")
                    ->orWhere('promise', 'like', "%{$q}%")
                    ->orWhere('realization', 'like', "%{$q}%");
            });
        }

        $trackers = $query->orderBy('order', 'asc')
            ->orderByDesc('last_updated_date')
            ->get()
            ->map(fn (PolicyTracker $t) => [
                'id' => $t->id,
                'program_name' => $t->program_name,
                'slug' => $t->slug,
                'sector' => $t->sector,
                'promise' => $t->promise,
                'realization' => $t->realization,
                'progress_percent' => $t->progress_percent,
                'status_label' => $t->status_label,
                'source_title' => $t->source_title,
                'source_url' => $t->source_url,
                'last_updated_date' => $t->last_updated_date?->format('Y-m-d'),
                'last_updated_formatted' => $t->last_updated_date ? \Carbon\Carbon::parse($t->last_updated_date)->locale('id')->isoFormat('D MMMM Y') : '',
            ])->values()->all();

        return response()->json([
            'data' => $trackers,
        ]);
    }

    /**
     * Helper voter hash untuk Reader Poll.
     */
    protected function getVoterHash(Request $request): string
    {
        $ip = $request->ip() ?? '127.0.0.1';
        $ua = $request->header('User-Agent') ?? 'unknown';
        $userId = $request->user()?->id ? ('usr:' . $request->user()->id) : '';
        $cookie = $request->cookie('isf_voter_id') ?? '';
        return hash('sha256', $ip . '|' . $ua . '|' . $userId . '|' . $cookie);
    }

    /**
     * Format reader poll response dengan persentase dan status voting.
     */
    protected function formatPollResponse(ReaderPoll $poll, ?string $voterHash = null): array
    {
        $options = is_array($poll->options) ? $poll->options : [];
        $total = (int) $poll->total_votes;
        if ($total <= 0) {
            $total = array_sum(array_map(fn ($o) => (int) ($o['votes'] ?? 0), $options));
        }

        $votedOptionId = null;
        $hasVoted = false;
        if ($voterHash) {
            $vote = ReaderPollVote::where('poll_id', $poll->id)->where('voter_hash', $voterHash)->first();
            if ($vote) {
                $hasVoted = true;
                $votedOptionId = $vote->option_id;
            }
        }

        $formattedOptions = array_map(function ($opt) use ($total) {
            $votes = (int) ($opt['votes'] ?? 0);
            $pct = $total > 0 ? round(($votes / $total) * 100, 1) : 0;
            $optId = (string) ($opt['id'] ?? $opt['key'] ?? '');
            return [
                'id' => $optId,
                'key' => $optId,
                'label' => $opt['label'] ?? '',
                'votes' => $votes,
                'percentage' => $pct,
            ];
        }, $options);

        return [
            'id' => $poll->id,
            'question' => $poll->question,
            'slug' => $poll->slug,
            'description' => $poll->description,
            'options' => $formattedOptions,
            'total_votes' => $total,
            'status' => $poll->status,
            'is_featured' => (bool) $poll->is_featured,
            'is_closed' => $poll->status !== 'aktif',
            'has_voted' => $hasVoted,
            'voted_option_id' => $votedOptionId,
            'is_scientific' => false,
            'disclaimer' => 'Jajak Pendapat / Polling Persepsi Publik Non-Ilmiah',
        ];
    }

    /**
     * GET /api/isf/opini-publik
     *
     * Halaman landing rubrik Opini Publik (Op-Ed, Editorial, Surat Pembaca, Reader Poll & Kutipan).
     */
    public function opiniPublik(Request $request): JsonResponse
    {
        $subkategori = $request->query('subkategori') ? strtolower(trim((string) $request->query('subkategori'))) : null;
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(48, max(1, (int) $request->input('limit', 12)));

        // Subkategori Opini Publik
        $parent = Category::query()->terbit()->where('slug', 'opini')->first();
        $subcategories = [];
        if ($parent) {
            $subcategories = Category::query()
                ->terbit()
                ->where('parent_id', $parent->id)
                ->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'name', 'slug', 'description'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'description' => $c->description,
                ])
                ->values()
                ->all();
        }

        if (empty($subcategories)) {
            $subcategories = [
                ['name' => 'Op-Ed & Analisis Pakar', 'slug' => 'op-ed', 'description' => 'Tulisan gagasan dan analisis mendalam dari akademisi dan pakar.'],
                ['name' => 'Editorial ISF', 'slug' => 'editorial', 'description' => 'Pandangan resmi Dewan Redaksi Indonesia Strategy Forum.'],
                ['name' => 'Surat Pembaca', 'slug' => 'surat-pembaca', 'description' => 'Aspirasi dan kontribusi pemikiran langsung dari pembaca.'],
            ];
        }

        // Query artikel opini
        $query = Article::query()
            ->terbit()
            ->where('category', 'opini')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($subkategori && in_array($subkategori, ['op-ed', 'editorial', 'surat-pembaca'], true)) {
            $subQuery = (clone $query)->where(function ($q) use ($subkategori) {
                $q->where('subcategory', $subkategori)
                    ->orWhere('category', $subkategori);
            });
            // Jika ada artikel dengan subkategori tersebut, terapkan filternya.
            // Jika subkategori belum memiliki artikel khusus, fallback menampilkan artikel terbaru opini agar filter tidak kosong.
            if ($subQuery->count() > 0) {
                $query = $subQuery;
            }
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $limit));
        $articles = $query->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $categoryLabel = $this->categoryLabeler();
        $cardMapper = $this->articleCardMapper($categoryLabel);
        $mappedArticles = $articles->map(function ($art) use ($cardMapper) {
            $card = $cardMapper($art);
            $card['is_editorial'] = $art->subcategory === 'editorial';
            $card['excerpt'] = $art->summary ?? '';
            $card['author_name'] = $art->author_name ?: 'Tim Riset ISF';
            $card['author_affiliation'] = $art->author_role ?? '';
            return $card;
        })->values()->all();

        // Headline
        $headline = null;
        if ($page === 1 && count($mappedArticles) > 0) {
            $headline = $mappedArticles[0];
        }
        $listArticles = $mappedArticles;

        // Reader Poll (Aktif & Featured)
        $voterHash = $this->getVoterHash($request);
        $activePolls = ReaderPoll::query()->aktif()
            ->orderByDesc('is_featured')
            ->orderBy('order', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ReaderPoll $p) => $this->formatPollResponse($p, $voterHash))
            ->values()
            ->all();
        $formattedPoll = count($activePolls) > 0 ? $activePolls[0] : null;

        // Kutipan Publik (max 6)
        $quotes = PublicQuote::query()->terbit()
            ->with('tokoh:id,name,slug,position,photo_url')
            ->orderBy('order', 'asc')
            ->orderByDesc('quote_date')
            ->orderByDesc('id')
            ->take(6)
            ->get()
            ->map(fn (PublicQuote $q) => [
                'id' => $q->id,
                'quote' => $q->quote,
                'kutipan' => $q->quote,
                'author_name' => $q->author_name,
                'tokoh_nama' => $q->author_name ?: ($q->tokoh?->name ?? 'Anonim'),
                'author_title' => $q->author_title,
                'tokoh_jabatan' => $q->author_title ?: ($q->tokoh?->position ?? ''),
                'author_type' => $q->author_type,
                'tokoh_tipe' => $q->author_type ?: ($q->tokoh_id ? 'tokoh' : 'netizen'),
                'topic' => $q->topic,
                'isu_terkait' => $q->topic,
                'source_title' => $q->source_title,
                'sumber_nama' => $q->source_title,
                'source_url' => $q->source_url,
                'sumber_url' => $q->source_url,
                'quote_date' => $q->quote_date?->format('Y-m-d'),
                'tanggal_kutipan' => $q->quote_date?->format('Y-m-d'),
                'quote_date_formatted' => $q->quote_date ? \Carbon\Carbon::parse($q->quote_date)->locale('id')->isoFormat('D MMMM Y') : '',
                'tokoh_id' => $q->tokoh_id,
                'tokoh' => $q->tokoh ? [
                    'id' => $q->tokoh->id,
                    'name' => $q->tokoh->name,
                    'slug' => $q->tokoh->slug,
                    'position' => $q->tokoh->position,
                    'photo_url' => $q->tokoh->photo_url,
                    'foto_url' => $q->tokoh->photo_url,
                    'to' => '/tokoh/' . $q->tokoh->slug,
                ] : null,
            ])->values()->all();

        // Surat Pembaca / Artikel by Member yang disetujui (max 6)
        $letters = Letter::query()->disetujui()->terbit()
            ->orderBy('order', 'asc')
            ->orderByDesc('published_at')
            ->take(6)
            ->get()
            ->map(fn (Letter $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'slug' => $l->slug,
                'body' => $l->body,
                'summary' => $l->summary,
                'category' => $l->category ?? 'Opini',
                'subcategory' => $l->subcategory,
                'sender_name' => $l->sender_name,
                'author_role' => $l->author_role,
                'author_avatar' => $l->author_avatar,
                'image_url' => $l->image_url,
                'sender_city' => $l->sender_city,
                'published_at' => $l->published_at?->format('Y-m-d'),
                'published_at_formatted' => $l->published_at ? \Carbon\Carbon::parse($l->published_at)->locale('id')->isoFormat('D MMMM Y') : '',
            ])->values()->all();

        // 4 Artikel Sorotan Member (terbaru yang dibuat/disetujui member)
        $memberArticles = Article::query()->terbit()->where('is_by_member', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(4)
            ->get();
        $sorotanMember = $memberArticles->map(function ($art) use ($cardMapper) {
            $card = $cardMapper($art);
            $card['is_by_member'] = true;
            $card['author_avatar'] = $art->author_avatar;
            $card['author_name'] = $art->author_name ?: 'Member Terverifikasi ISF';
            $card['author_affiliation'] = $art->author_role ?: 'Member ISF';
            $card['author_slug'] = Str::slug($card['author_name'] ?: 'penulis');
            $card['summary'] = $art->summary ?? '';
            return $card;
        })->values()->all();

        if (count($sorotanMember) < 4) {
            $needed = 4 - count($sorotanMember);
            $existingSlugs = collect($sorotanMember)->pluck('slug')->all();
            $extraLetters = Letter::query()->disetujui()->terbit()
                ->whereNotIn('slug', $existingSlugs)
                ->orderBy('order', 'asc')
                ->orderByDesc('published_at')
                ->take($needed)
                ->get();
            foreach ($extraLetters as $l) {
                $sorotanMember[] = [
                    'slug' => $l->slug,
                    'title' => $l->title,
                    'category' => $l->category ?? 'Opini',
                    'subcategory' => $l->subcategory ?? 'Artikel Member',
                    'summary' => $l->summary ?? Str::limit(strip_tags($l->body), 150),
                    'publishedAt' => $l->published_at?->format('Y-m-d'),
                    'readingMinutes' => $l->reading_minutes ?? 5,
                    'imageUrl' => $l->image_url,
                    'imageAlt' => $l->image_alt ?: $l->title,
                    'author' => $l->sender_name,
                    'author_name' => $l->sender_name,
                    'author_slug' => Str::slug($l->sender_name ?: 'penulis'),
                    'author_affiliation' => $l->author_role ?: ($l->sender_city ?: 'Member ISF'),
                    'author_avatar' => $l->author_avatar,
                    'is_by_member' => true,
                    'to' => '/artikel/' . $l->slug,
                ];
            }
        }

        return response()->json([
            'rubrik' => [
                'key' => 'opini',
                'title' => 'Opini Publik',
                'label' => 'Opini Publik',
                'description' => $parent?->description ?: 'Wadah pemikiran kritis, pandangan resmi redaksi, dan suara kontribusi publik.',
                'subkategori' => $subcategories,
                'activeSubkategori' => $subkategori,
            ],
            'headline' => $headline,
            'articles' => $listArticles,
            'sorotanMember' => $sorotanMember,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
            'readerPoll' => $formattedPoll,
            'readerPolls' => $activePolls,
            'kutipanPublik' => $quotes,
            'suratPembaca' => $letters,
        ]);
    }

    /**
     * GET /api/isf/penulis/{slug}
     *
     * Detail profil penulis / kontributor opini publik beserta daftar karya artikelnya.
     */
    public function penulis(Request $request, string $slug): JsonResponse
    {
        $normalizedSlug = Str::slug($slug);

        // Cari di Letter (artikel by member)
        $letters = Letter::query()->disetujui()->terbit()->get();
        $matchingLetters = $letters->filter(function (Letter $l) use ($normalizedSlug) {
            $authorSlug = Str::slug($l->sender_name ?: '');
            return $authorSlug === $normalizedSlug || Str::contains($authorSlug, $normalizedSlug) || Str::contains($normalizedSlug, $authorSlug);
        });

        // Cari di Article (artikel umum / by member)
        $articles = Article::query()->terbit()->get();
        $matchingArticles = $articles->filter(function (Article $a) use ($normalizedSlug) {
            $authorSlug = Str::slug($a->author_name ?: '');
            return $authorSlug === $normalizedSlug || Str::contains($authorSlug, $normalizedSlug) || Str::contains($normalizedSlug, $authorSlug);
        });

        // Cari di User
        $user = \App\Models\User::where('name', 'like', "%{$slug}%")
            ->orWhereRaw("LOWER(REPLACE(name, ' ', '-')) = ?", [$normalizedSlug])
            ->first();

        if ($matchingLetters->isEmpty() && $matchingArticles->isEmpty() && ! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Profil penulis tidak ditemukan.',
            ], 404);
        }

        // Tentukan data identitas penulis
        $sampleLetter = $matchingLetters->first();
        $sampleArticle = $matchingArticles->first();

        $authorName = $sampleLetter?->sender_name
            ?: ($sampleArticle?->author_name
            ?: ($user?->name ?: ucwords(str_replace('-', ' ', $slug))));

        $authorRole = $sampleLetter?->author_role
            ?: ($sampleArticle?->author_role
            ?: ($sampleLetter?->sender_city ? 'Member ISF · ' . $sampleLetter->sender_city : 'Member Terverifikasi ISF'));

        $authorAvatar = $sampleLetter?->author_avatar
            ?: ($sampleArticle?->author_avatar ?: null);

        $authorCity = $sampleLetter?->sender_city ?: null;

        // Kumpulkan daftar artikel unik karya penulis
        $categoryLabel = $this->categoryLabeler();
        $cardMapper = $this->articleCardMapper($categoryLabel);

        $combinedArticles = collect();

        foreach ($matchingArticles as $art) {
            $card = $cardMapper($art);
            $card['is_by_member'] = (bool) $art->is_by_member;
            $card['author_avatar'] = $art->author_avatar ?: $authorAvatar;
            $card['author_name'] = $art->author_name ?: $authorName;
            $card['author_affiliation'] = $art->author_role ?: $authorRole;
            $card['summary'] = $art->summary ?? '';
            $combinedArticles->push($card);
        }

        $existingSlugs = $combinedArticles->pluck('slug')->all();

        foreach ($matchingLetters as $l) {
            if (! in_array($l->slug, $existingSlugs, true)) {
                $combinedArticles->push([
                    'slug' => $l->slug,
                    'title' => $l->title,
                    'category' => $l->category ?? 'Opini',
                    'subcategory' => $l->subcategory ?? 'Artikel Member',
                    'summary' => $l->summary ?? Str::limit(strip_tags($l->body), 160),
                    'publishedAt' => $l->published_at?->format('Y-m-d'),
                    'readingMinutes' => $l->reading_minutes ?? 5,
                    'imageUrl' => $l->image_url,
                    'imageAlt' => $l->image_alt ?: $l->title,
                    'author' => $l->sender_name,
                    'author_name' => $l->sender_name,
                    'author_affiliation' => $l->author_role ?: ($l->sender_city ?: 'Member ISF'),
                    'author_avatar' => $l->author_avatar ?: $authorAvatar,
                    'is_by_member' => true,
                    'to' => '/artikel/' . $l->slug,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $authorName,
                'slug' => $normalizedSlug,
                'role' => $authorRole,
                'city' => $authorCity,
                'avatar' => $authorAvatar,
                'bio' => 'Kontributor aktif kanal Opini Publik Indonesia Strategy Forum (ISF). Berkomitmen menyajikan ulasan kritis, bernas, dan berorientasi pada kemajuan kebijakan bangsa.',
                'total_articles' => $combinedArticles->count(),
                'articles' => $combinedArticles->values()->all(),
            ],
        ]);
    }

    /**
     * GET /api/isf/reader-poll
     *
     * Data polling aktif untuk pembaca.
     */
    public function readerPoll(Request $request): JsonResponse
    {
        $voterHash = $this->getVoterHash($request);
        $polls = ReaderPoll::query()
            ->whereIn('status', ['aktif', 'selesai'])
            ->orderByDesc('is_featured')
            ->orderBy('order', 'asc')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ReaderPoll $p) => $this->formatPollResponse($p, $voterHash));

        return response()->json([
            'data' => $polls,
        ]);
    }

    /**
     * POST /api/isf/reader-poll/{id}/vote
     *
     * Berikan suara pada reader poll (1 suara per perangkat / voter hash).
     */
    public function voteReaderPoll(Request $request, int $id): JsonResponse
    {
        $optionId = (string) ($request->input('option_id') ?? $request->input('option_key') ?? '');
        if ($optionId === '') {
            return response()->json([
                'message' => 'The option id field is required.',
                'errors' => ['option_id' => ['The option id field is required.']],
            ], 422);
        }

        $poll = ReaderPoll::find($id);
        if (! $poll) {
            return response()->json(['message' => 'Polling tidak ditemukan.'], 404);
        }

        if ($poll->status !== 'aktif') {
            return response()->json(['message' => 'Polling ini sudah ditutup atau tidak aktif.'], 422);
        }

        $voterHash = $this->getVoterHash($request);

        // Cek apakah perangkat/voter ini sudah memberikan suara
        $existing = ReaderPollVote::where('poll_id', $poll->id)
            ->where('voter_hash', $voterHash)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Anda sudah memberikan suara pada jajak pendapat ini.',
                'voted_option_id' => $existing->option_id,
                'poll' => $this->formatPollResponse($poll, $voterHash),
            ], 422);
        }

        $options = is_array($poll->options) ? $poll->options : [];
        $found = false;

        foreach ($options as &$opt) {
            $currKey = (string) ($opt['id'] ?? $opt['key'] ?? '');
            if ($currKey === $optionId) {
                $opt['votes'] = ((int) ($opt['votes'] ?? 0)) + 1;
                $found = true;
                break;
            }
        }
        unset($opt);

        if (! $found) {
            return response()->json(['message' => 'Opsi pilihan tidak valid.'], 422);
        }

        ReaderPollVote::create([
            'poll_id' => $poll->id,
            'option_id' => $optionId,
            'voter_hash' => $voterHash,
            'ip_address' => $request->ip(),
            'user_id' => $request->user()?->id,
        ]);

        $poll->options = $options;
        $poll->total_votes = ((int) $poll->total_votes) + 1;
        $poll->save();

        return response()->json([
            'success' => true,
            'message' => 'Suara Anda berhasil dicatat. Terima kasih telah berpartisipasi!',
            'data' => [
                'id' => $poll->id,
                'total_votes' => $poll->total_votes,
                'voted_option_id' => $optionId,
            ],
            'voted_option_id' => $optionId,
            'poll' => $this->formatPollResponse($poll, $voterHash),
        ]);
    }

    /**
     * GET /api/isf/kutipan-publik
     *
     * Kumpulan kutipan tajam tokoh & netizen.
     */
    public function kutipanPublik(Request $request): JsonResponse
    {
        $authorType = $request->query('author_type') ? strtolower(trim((string) $request->query('author_type'))) : null;
        $q = trim((string) $request->input('q', ''));

        $query = PublicQuote::query()->terbit()
            ->with('tokoh:id,name,slug,position,photo_url');

        if ($authorType && in_array($authorType, ['tokoh', 'netizen'], true)) {
            $query->where('author_type', $authorType);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('quote', 'like', "%{$q}%")
                    ->orWhere('author_name', 'like', "%{$q}%")
                    ->orWhere('topic', 'like', "%{$q}%");
            });
        }

        $quotes = $query->orderBy('order', 'asc')
            ->orderByDesc('quote_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PublicQuote $q) => [
                'id' => $q->id,
                'quote' => $q->quote,
                'author_name' => $q->author_name,
                'author_title' => $q->author_title,
                'author_type' => $q->author_type,
                'topic' => $q->topic,
                'source_title' => $q->source_title,
                'source_url' => $q->source_url,
                'quote_date' => $q->quote_date?->format('Y-m-d'),
                'quote_date_formatted' => $q->quote_date ? \Carbon\Carbon::parse($q->quote_date)->locale('id')->isoFormat('D MMMM Y') : '',
                'tokoh' => $q->tokoh ? [
                    'id' => $q->tokoh->id,
                    'name' => $q->tokoh->name,
                    'slug' => $q->tokoh->slug,
                    'position' => $q->tokoh->position,
                    'to' => '/tokoh/' . $q->tokoh->slug,
                ] : null,
            ])->values()->all();

        return response()->json(['data' => $quotes]);
    }

    /**
     * GET /api/isf/surat-pembaca
     *
     * Daftar surat pembaca yang telah disetujui redaksi.
     */
    public function suratPembaca(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(20, max(1, (int) $request->input('limit', 10)));
        $q = trim((string) $request->input('q', ''));

        $query = Letter::query()->disetujui()->terbit();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")
                    ->orWhere('sender_name', 'like', "%{$q}%")
                    ->orWhere('sender_city', 'like', "%{$q}%");
            });
        }

        $total = (clone $query)->count();
        $letters = $query->orderBy('order', 'asc')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get()
            ->map(fn (Letter $l) => [
                'id' => $l->id,
                'title' => $l->title,
                'slug' => $l->slug,
                'body' => $l->body,
                'sender_name' => $l->sender_name,
                'sender_city' => $l->sender_city,
                'published_at' => $l->published_at?->format('Y-m-d'),
                'published_at_formatted' => $l->published_at ? \Carbon\Carbon::parse($l->published_at)->locale('id')->isoFormat('D MMMM Y') : '',
            ]);

        return response()->json([
            'data' => $letters,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => max(1, (int) ceil($total / $limit)),
                'total' => $total,
                'perPage' => $limit,
            ],
        ]);
    }

    /**
     * POST /api/isf/surat-pembaca
     *
     * Kirim surat pembaca publik dengan validasi captcha.
     */
    public function submitSuratPembaca(LetterSubmissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slug = Str::slug($validated['title']) . '-' . Str::lower(Str::random(5));

        $letter = Letter::create([
            'user_id' => $request->user()?->id,
            'sender_name' => $validated['sender_name'],
            'sender_email' => $validated['sender_email'],
            'sender_city' => $validated['sender_city'] ?? null,
            'author_role' => $validated['author_role'] ?? null,
            'author_avatar' => $validated['author_avatar'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'category' => $validated['category'] ?? 'kebijakan',
            'subcategory' => $validated['subcategory'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'body' => $validated['body'],
            'reading_minutes' => $validated['reading_minutes'] ?? 5,
            'image_url' => $validated['image_url'] ?? null,
            'image_alt' => $validated['image_alt'] ?? null,
            'link_url' => $validated['link_url'] ?? null,
            'fact_blocks' => $validated['fact_blocks'] ?? null,
            'moderation_status' => 'pending',
            'status' => 'terbit',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artikel Anda berhasil dikirim dan masuk antrean moderasi redaksi. Artikel akan ditayangkan setelah disetujui.',
            'data' => [
                'id' => $letter->id,
                'title' => $letter->title,
                'moderation_status' => $letter->moderation_status,
            ],
        ], 201);
    }

    /**
     * GET /api/isf/search
     *
     * Mesin pencarian terpadu lintas modul konten ISF:
     * - Artikel (Beranda, Isu, Kebijakan, Politik, Hukum)
     * - Profil Tokoh (Tokoh Publik & Tokoh Pekan Ini)
     * - Infografik (Data visual regulasi & kebijakan)
     * - Briefing (Executive email briefing)
     * - Video (Media Sorotan & Video Analisis)
     */
    public function search(Request $request): JsonResponse
    {
        // Batasi panjang kata kunci, lalu escape wildcard LIKE (% _ \) agar dicari sebagai teks biasa.
        $query = mb_substr(trim((string) $request->input('q', '')), 0, 100);
        $like = '%' . addcslashes($query, '%_\\') . '%';
        $type = strtolower(trim((string) $request->input('type', 'semua')));

        $suggestions = [
            'Politik' => [
                'RUU Perampasan Aset',
                'Koalisi Partai',
                'Reshuffle Kabinet',
                'Pilkada Serentak',
                'Pemilu',
                'Ambang Batas Parlemen',
            ],
            'Hukum' => [
                'Mahkamah Konstitusi',
                'Mahkamah Agung',
                'Praperadilan',
                'UU ITE',
                'Penegakan Tipikor',
                'Reformasi Kepolisian',
            ],
            'Kebijakan' => [
                'Hilirisasi Nikel',
                'Transisi Energi Hijau',
                'Defisit APBN',
                'Pajak PPN 12%',
                'Subsidi Tepat Sasaran',
                'IKN Nusantara',
            ],
            'Geopolitik' => [
                'Diplomasi ASEAN',
                'Aksesi OECD',
                'Keanggotaan BRICS',
                'Laut Natuna Utara',
                'Ketahanan Pangan',
            ],
            'Tokoh' => [
                'Prabowo Subianto',
                'Gibran Rakabuming',
                'Sri Mulyani',
                'Erick Thohir',
                'Puan Maharani',
                'Mahfud MD',
            ],
        ];

        $popularKeywords = [
            'RUU Perampasan Aset',
            'Hilirisasi Nikel',
            'Mahkamah Konstitusi',
            'Transisi Energi Hijau',
            'Prabowo Subianto',
            'IKN Nusantara',
            'Koalisi Partai',
            'Pajak PPN 12%',
        ];

        // 1. Artikel
        $articleQuery = Article::query()->terbit();
        if ($query !== '') {
            $articleQuery->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('summary', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('author_name', 'like', $like);
            });
        }
        $articles = $articleQuery->orderByDesc('published_at')->orderByDesc('id')->take(100)->get()->map(function (Article $a) use ($query) {
            $summary = $a->summary ?: 'Analisis mendalam kebijakan dan regulasi strategis nasional.';
            // Cuplikan diambil dari bagian yang memuat kata kunci: ringkasan dulu, lalu isi artikel.
            $snippetSource = $summary;
            if ($query !== '' && mb_stripos($summary, $query) === false && $a->body
                && mb_stripos(strip_tags((string) $a->body), $query) !== false) {
                $snippetSource = (string) $a->body;
            }
            return [
                'id' => (string) $a->id,
                'type' => 'artikel',
                'typeLabel' => 'Artikel',
                'title' => $a->title,
                'kicker' => $a->category ? strtoupper($a->category) : 'ANALISIS',
                'category' => $a->category ?: 'Artikel',
                'summary' => $summary,
                'snippet' => $this->buildSnippet($snippetSource, $query),
                'publishedAt' => $a->published_at ? $a->published_at->format('Y-m-d') : null,
                'author' => $a->author_name ?: 'Tim Riset ISF',
                'imageUrl' => $a->image_url ?: 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=800&auto=format&fit=crop',
                'to' => $a->publicUrl(),
                'readingMinutes' => $a->reading_minutes ?: 5,
            ];
        });

        // 2. Profil Tokoh
        $tokohQuery = Tokoh::query()->terbit();
        if ($query !== '') {
            $tokohQuery->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                    ->orWhere('position', 'like', $like)
                    ->orWhere('teaser', 'like', $like)
                    ->orWhere('skor_explanation', 'like', $like);
            });
        }
        $tokohs = $tokohQuery->orderByDesc('updated_at')->take(60)->get()->map(function (Tokoh $t) use ($query) {
            $summary = $t->teaser ?: ($t->skor_explanation ?: ($t->position ?: 'Profil tokoh publik dan pembuat kebijakan strategis.'));
            return [
                'id' => (string) $t->id,
                'type' => 'tokoh',
                'typeLabel' => 'Profil Tokoh',
                'title' => $t->name,
                'kicker' => $t->position ? strtoupper($t->position) : 'TOKOH PUBLIK',
                'category' => 'Tokoh',
                'summary' => $summary,
                'snippet' => $this->buildSnippet($summary, $query),
                'publishedAt' => null,
                'author' => 'Redaksi ISF',
                'imageUrl' => $t->photo_url ?: 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=800&auto=format&fit=crop',
                'to' => '/tokoh/' . $t->slug,
                'readingMinutes' => null,
            ];
        });

        // 3. Infografik
        $infographicQuery = Infographic::query()->terbit();
        if ($query !== '') {
            $infographicQuery->where(function ($w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('category', 'like', $like);
            });
        }
        $infographics = $infographicQuery->orderByDesc('published_at')->take(60)->get()->map(function (Infographic $inf) use ($query) {
            $summary = 'Infografik dan visualisasi data: ' . $inf->title;
            return [
                'id' => (string) $inf->id,
                'type' => 'infografik',
                'typeLabel' => 'Infografik',
                'title' => $inf->title,
                'kicker' => $inf->category ? strtoupper($inf->category) : 'INFOGRAFIK',
                'category' => $inf->category ?: 'Infografik',
                'summary' => $summary,
                'snippet' => $this->buildSnippet($summary, $query),
                'publishedAt' => $inf->published_at ? $inf->published_at->format('Y-m-d') : null,
                'author' => 'Tim Data & Riset ISF',
                'imageUrl' => $inf->image_url ?: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=800&auto=format&fit=crop',
                'to' => $inf->publicUrl(),
                'readingMinutes' => null,
            ];
        });

        // 4. Briefing
        $briefingQuery = Briefing::query()->terbit();
        if ($query !== '') {
            $briefingQuery->where(function ($w) use ($like) {
                $w->where('ringkasan_judul', 'like', $like)
                    ->orWhere('ringkasan_catatan', 'like', $like)
                    ->orWhere('pembuka', 'like', $like)
                    ->orWhere('penutup', 'like', $like);
            });
        }
        $briefings = $briefingQuery->orderByDesc('tanggal')->take(60)->get()->map(function (Briefing $b) use ($query) {
            $summary = $b->ringkasan_catatan ?: ($b->pembuka ?: 'Briefing intelijen kebijakan dan regulasi strategis.');
            return [
                'id' => (string) $b->id,
                'type' => 'briefing',
                'typeLabel' => 'Briefing',
                'title' => $b->ringkasan_judul ?: 'Email Briefing Eksekutif',
                'kicker' => 'EXECUTIVE BRIEFING',
                'category' => 'Briefing',
                'summary' => $summary,
                'snippet' => $this->buildSnippet($summary, $query),
                'publishedAt' => $b->tanggal ? $b->tanggal->format('Y-m-d') : null,
                'author' => 'Dewan Pakar ISF',
                'imageUrl' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?q=80&w=800&auto=format&fit=crop',
                'to' => '/anggota/briefing/preview',
                'readingMinutes' => 4,
            ];
        });

        // 5. Video (Media Sorotan)
        $settings = Setting::get('beranda', []);
        $mediaSorotan = $settings['mediaSorotan'] ?? [];
        $activeMedia = array_values(array_filter($mediaSorotan, fn($item) => ($item['status'] ?? 'terbit') === 'terbit'));
        if (empty($activeMedia)) {
            $activeMedia = $this->defaultMediaItems();
        }

        $videos = collect($activeMedia);
        if ($query !== '') {
            $videos = $videos->filter(function ($item) use ($query) {
                $q = mb_strtolower($query);
                return str_contains(mb_strtolower($item['title'] ?? ''), $q)
                    || str_contains(mb_strtolower($item['summary'] ?? ''), $q)
                    || str_contains(mb_strtolower($item['content'] ?? ''), $q)
                    || str_contains(mb_strtolower($item['category'] ?? ''), $q)
                    || str_contains(mb_strtolower($item['author'] ?? ''), $q);
            });
        }
        $videos = $videos->take(60)->map(function ($item) use ($query) {
            $summary = $item['summary'] ?? ($item['title'] . ' — Diskusi dan ulasan strategis dari Dewan Pakar Indonesia Strategy Forum.');
            return [
                'id' => (string) ($item['id'] ?? '1'),
                'type' => 'video',
                'typeLabel' => 'Video',
                'title' => $item['title'] ?? 'Video Analisis',
                'kicker' => strtoupper($item['category'] ?? 'VIDEO ANALISIS'),
                'category' => $item['category'] ?? 'Video',
                'summary' => $summary,
                'snippet' => $this->buildSnippet($summary, $query),
                'publishedAt' => $item['publishedAt'] ?? date('Y-m-d'),
                'author' => $item['author'] ?? 'Tim Redaksi ISF',
                'imageUrl' => !empty($item['imageUrl']) ? $item['imageUrl'] : 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=800&auto=format&fit=crop',
                'to' => '/media/' . ($item['id'] ?? '1'),
                'readingMinutes' => null,
                'duration' => $item['duration'] ?? '15:20',
            ];
        });

        // Curated strategic topic fallbacks: if query matches specific strategic topics and DB has few matches
        if ($query !== '') {
            $curated = $this->getCuratedStrategicMatches($query);
            if (!empty($curated['articles']) && $articles->count() < 3) {
                $articles = $articles->concat($curated['articles'])->unique('title');
            }
            if (!empty($curated['tokohs']) && $tokohs->count() < 2) {
                $tokohs = $tokohs->concat($curated['tokohs'])->unique('title');
            }
            if (!empty($curated['infographics']) && $infographics->count() < 2) {
                $infographics = $infographics->concat($curated['infographics'])->unique('title');
            }
            if (!empty($curated['briefings']) && $briefings->count() < 2) {
                $briefings = $briefings->concat($curated['briefings'])->unique('title');
            }
            if (!empty($curated['videos']) && $videos->count() < 2) {
                $videos = $videos->concat($curated['videos'])->unique('title');
            }
        }

        $counts = [
            'semua' => $articles->count() + $tokohs->count() + $infographics->count() + $briefings->count() + $videos->count(),
            'artikel' => $articles->count(),
            'tokoh' => $tokohs->count(),
            'infografik' => $infographics->count(),
            'briefing' => $briefings->count(),
            'video' => $videos->count(),
        ];

        $results = collect();
        if ($type === 'semua' || $type === '') {
            $results = $results->concat($articles)
                ->concat($tokohs)
                ->concat($infographics)
                ->concat($briefings)
                ->concat($videos);
        } elseif ($type === 'artikel') {
            $results = $articles;
        } elseif ($type === 'tokoh') {
            $results = $tokohs;
        } elseif ($type === 'infografik') {
            $results = $infographics;
        } elseif ($type === 'briefing') {
            $results = $briefings;
        } elseif ($type === 'video') {
            $results = $videos;
        }

        return response()->json([
            'query' => $query,
            'type' => $type,
            'total' => $results->count(),
            'counts' => $counts,
            'data' => $results->values(),
            'suggestions' => $suggestions,
            'popularKeywords' => $popularKeywords,
        ]);
    }

    /**
     * Helper untuk mengekstrak cuplikan kalimat relevan berbasis kata kunci pencarian.
     */
    private function buildSnippet(string $text, string $query): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if ($query === '' || mb_strlen($clean) <= 160) {
            return Str::limit($clean, 160);
        }

        $pos = mb_stripos($clean, $query);
        if ($pos === false) {
            return Str::limit($clean, 160);
        }

        $start = max(0, $pos - 50);
        $snippet = mb_substr($clean, $start, 150);

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }
        if ($start + 150 < mb_strlen($clean)) {
            $snippet .= '...';
        }

        return $snippet;
    }

    /**
     * Konten kurasi strategis untuk kata kunci AI Suggestions jika belum terisi di database.
     */
    private function getCuratedStrategicMatches(string $query): array
    {
        $q = mb_strtolower($query);
        $catalog = [
            'nikel' => [
                'articles' => [
                    [
                        'id' => 'cur-nikel-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Evaluasi Hilirisasi Nikel: Keseimbangan Nilai Tambah dan Kepatuhan ESG Industri',
                        'kicker' => 'KEBIJAKAN INDUSTRI',
                        'category' => 'Kebijakan',
                        'summary' => 'Pemerintah merumuskan peta jalan hilirisasi nikel generasi lanjutan dengan memperketat audit lingkungan serta standarisasi dekarbonisasi smelter.',
                        'snippet' => '...evaluasi hilirisasi nikel nasional berfokus pada kepatuhan ESG industri dan nilai tambah ekonomi berkelanjutan...',
                        'publishedAt' => '2026-11-08',
                        'author' => 'Tim Riset Industri & Energi ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 6,
                    ],
                ],
                'infographics' => [
                    [
                        'id' => 'cur-nikel-info',
                        'type' => 'infografik',
                        'typeLabel' => 'Infografik',
                        'title' => 'Peta Smelter & Rantai Pasok Ekosistem Baterai Listrik Nasional',
                        'kicker' => 'DATA INDUSTRI',
                        'category' => 'Infografik',
                        'summary' => 'Sebaran kapasitas produksi smelter nikel dan rantai pasok hilirisasi bahan baku baterai kendaraan listrik.',
                        'snippet' => '...visualisasi sebaran hilirisasi nikel dan proyeksi kebutuhan energi smelter terbarukan...',
                        'publishedAt' => '2026-11-01',
                        'author' => 'Tim Data ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=800&auto=format&fit=crop',
                        'to' => '/infografik',
                        'readingMinutes' => null,
                    ],
                ],
            ],
            'energi' => [
                'articles' => [
                    [
                        'id' => 'cur-ebt-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Transisi Energi Hijau: Harmonisasi Insentif Pajak Karbon & Investasi EBT',
                        'kicker' => 'TRANSISI ENERGI',
                        'category' => 'Kebijakan',
                        'summary' => 'Kajian peta jalan transisi energi bersih dengan skema percepatan bauran EBT dan kesiapan mekanisme perdagangan karbon nasional.',
                        'snippet' => '...akselerasi transisi energi hijau memerlukan kepastian regulasi insentif fiskal dan penyelarasan tarif listrik...',
                        'publishedAt' => '2026-11-06',
                        'author' => 'Dewan Pakar Fiskal & Energi ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 5,
                    ],
                ],
            ],
            'ikn' => [
                'articles' => [
                    [
                        'id' => 'cur-ikn-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Pembangunan IKN Nusantara: Tinjauan Realisasi Investasi & Skema KPBU 2026',
                        'kicker' => 'INFRASTRUKTUR',
                        'category' => 'Kebijakan',
                        'summary' => 'Evaluasi skema kerja sama pemerintah dan badan usaha (KPBU) dalam akselerasi infrastruktur pusat pemerintahan di IKN Nusantara.',
                        'snippet' => '...kelanjutan pembangunan IKN Nusantara menuntut tata kelola perizinan terpadu dan mitigasi risiko fiskal jangka panjang...',
                        'publishedAt' => '2026-11-05',
                        'author' => 'Tim Analis Kebijakan Publik ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 6,
                    ],
                ],
            ],
            'pajak' => [
                'articles' => [
                    [
                        'id' => 'cur-pajak-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Pemberlakuan Tarif Pajak PPN 12%: Dampak Daya Beli dan Penyesuaian Strategi Pelaku Usaha',
                        'kicker' => 'FISKAL & MONETER',
                        'category' => 'Kebijakan',
                        'summary' => 'Simulasi dampak penyesuaian tarif PPN 12% terhadap inflasi barang konsumsi dan rekomendasi mitigasi bagi sektor manufaktur dan ritel.',
                        'snippet' => '...kebijakan tarif pajak PPN 12% perlu diimbangi dengan bantalan perlindungan sosial dan relaksasi administrasi perpajakan...',
                        'publishedAt' => '2026-11-03',
                        'author' => 'Dewan Pakar Ekonomi Makro ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 5,
                    ],
                ],
            ],
            'prabowo' => [
                'tokohs' => [
                    [
                        'id' => 'cur-tokoh-prabowo',
                        'type' => 'tokoh',
                        'typeLabel' => 'Profil Tokoh',
                        'title' => 'Prabowo Subianto',
                        'kicker' => 'PRESIDEN REPUBLIK INDONESIA',
                        'category' => 'Tokoh',
                        'summary' => 'Kepala Negara yang memimpin implementasi program Asta Cita, penguatan kedaulatan pangan, ketahanan energi, dan modernisasi pertahanan.',
                        'snippet' => '...kebijakan strategis Presiden Prabowo Subianto dalam memantapkan stabilitas geopolitik dan diplomasi ekonomi internasional...',
                        'publishedAt' => null,
                        'author' => 'Redaksi ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?q=80&w=800&auto=format&fit=crop',
                        'to' => '/tokoh/budi-santoso',
                        'readingMinutes' => null,
                    ],
                ],
            ],
            'gibran' => [
                'tokohs' => [
                    [
                        'id' => 'cur-tokoh-gibran',
                        'type' => 'tokoh',
                        'typeLabel' => 'Profil Tokoh',
                        'title' => 'Gibran Rakabuming Raka',
                        'kicker' => 'WAKIL PRESIDEN REPUBLIK INDONESIA',
                        'category' => 'Tokoh',
                        'summary' => 'Wakil Presiden RI yang berfokus pada akselerasi hilirisasi digital, penguatan talenta muda, dan pengawasan proyek strategis nasional.',
                        'snippet' => '...agenda prioritas Wakil Presiden Gibran Rakabuming dalam transformasi digital birokrasi dan ekosistem kewirausahaan muda...',
                        'publishedAt' => null,
                        'author' => 'Redaksi ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=800&auto=format&fit=crop',
                        'to' => '/tokoh/budi-santoso',
                        'readingMinutes' => null,
                    ],
                ],
            ],
            'sri mulyani' => [
                'tokohs' => [
                    [
                        'id' => 'cur-tokoh-sm',
                        'type' => 'tokoh',
                        'typeLabel' => 'Profil Tokoh',
                        'title' => 'Sri Mulyani Indrawati',
                        'kicker' => 'MENTERI KEUANGAN RI',
                        'category' => 'Tokoh',
                        'summary' => 'Arsitek kebijakan fiskal nasional yang menjaga disiplin APBN, kredibilitas penerimaan negara, dan stabilitas makroekonomi.',
                        'snippet' => '...arah kebijakan fiskal Sri Mulyani dalam mengendalikan defisit anggaran dan menjaga rasio utang pemerintah...',
                        'publishedAt' => null,
                        'author' => 'Redaksi ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=800&auto=format&fit=crop',
                        'to' => '/tokoh/budi-santoso',
                        'readingMinutes' => null,
                    ],
                ],
            ],
            'mahkamah konstitusi' => [
                'articles' => [
                    [
                        'id' => 'cur-mk-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Yurisprudensi Mahkamah Konstitusi: Arah Baru Pengujian Formil Undang-Undang',
                        'kicker' => 'HUKUM TATA NEGARA',
                        'category' => 'Hukum',
                        'summary' => 'Kajian putusan mutakhir Mahkamah Konstitusi tentang asas meaningful participation dalam pembentukan perundang-undangan nasional.',
                        'snippet' => '...Mahkamah Konstitusi mempertegas kewajiban pembentuk undang-undang untuk menyerap partisipasi publik secara substantif...',
                        'publishedAt' => '2026-11-07',
                        'author' => 'Tim Riset Hukum Tata Negara ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 5,
                    ],
                ],
            ],
            'brics' => [
                'articles' => [
                    [
                        'id' => 'cur-brics-1',
                        'type' => 'artikel',
                        'typeLabel' => 'Artikel',
                        'title' => 'Aksesi Keanggotaan BRICS & OECD: Implikasi Strategis bagi Arsitektur Perdagangan Nasional',
                        'kicker' => 'GEOPOLITIK & EKONOMI GLOBAL',
                        'category' => 'Politik',
                        'summary' => 'Analisis politik luar negeri bebas aktif Indonesia dalam menyeimbangkan kemitraan multilateral antara blok ekonomi barat dan selatan global.',
                        'snippet' => '...diplomasi multilateral dalam aksesi BRICS dan OECD membuka peluang diversifikasi pasar ekspor komoditas strategis...',
                        'publishedAt' => '2026-11-04',
                        'author' => 'Pusat Kajian Geopolitik ISF',
                        'imageUrl' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop',
                        'to' => '/beranda',
                        'readingMinutes' => 7,
                    ],
                ],
            ],
        ];

        $matchedArticles = [];
        $matchedTokohs = [];
        $matchedInfographics = [];
        $matchedBriefings = [];
        $matchedVideos = [];

        foreach ($catalog as $key => $items) {
            if (str_contains($q, $key) || str_contains($key, $q)) {
                if (!empty($items['articles']))
                    $matchedArticles = array_merge($matchedArticles, $items['articles']);
                if (!empty($items['tokohs']))
                    $matchedTokohs = array_merge($matchedTokohs, $items['tokohs']);
                if (!empty($items['infographics']))
                    $matchedInfographics = array_merge($matchedInfographics, $items['infographics']);
                if (!empty($items['briefings']))
                    $matchedBriefings = array_merge($matchedBriefings, $items['briefings']);
                if (!empty($items['videos']))
                    $matchedVideos = array_merge($matchedVideos, $items['videos']);
            }
        }

        return [
            'articles' => collect($matchedArticles),
            'tokohs' => collect($matchedTokohs),
            'infographics' => collect($matchedInfographics),
            'briefings' => collect($matchedBriefings),
            'videos' => collect($matchedVideos),
        ];
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
            ->whereIn('category', IsfRubrik::categories('politik'))
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
                // Label = kategori CMS artikel (bukan teks tetap), sama dengan breadcrumb /artikel/{kategori}.
                'kicker' => $first->categoryLabel(),
                'category' => 'Politik',
                'summary' => $first->summary ?: 'Telaah peta koalisi parlemen dan dinamika lobi antar-fraksi terkait prioritas legislasi nasional.',
                'publishedAt' => $first->published_at ? $first->published_at->format('Y-m-d') : date('Y-m-d'),
                'author' => [
                    'name' => $first->author_name ?: 'Tim Riset Politik ISF',
                    'role' => $first->author_role ?: 'Analis Parlemen',
                ],
                'readingMinutes' => $first->reading_minutes ?: 5,
                'imageUrl' => $first->image_url ?: 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=1200&auto=format&fit=crop',
                'to' => $first->publicUrl(),
            ];

            foreach ($politikArticles as $art) {
                $politikItems[] = [
                    'id' => (string) $art->id,
                    'title' => $art->title,
                    'kicker' => $art->categoryLabel(),
                    'publishedAt' => $art->published_at ? $art->published_at->format('Y-m-d') : date('Y-m-d'),
                    'summary' => $art->summary ?: '',
                    'imageUrl' => $art->image_url ?: '',
                    'to' => $art->publicUrl(),
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

        // 2. Hukum (3 kolom, maks. 7 artikel): semua artikel Hukum terbit, urut terbaru,
        // diisi kiri (2) → tengah (1) → kanan (4). Artikel Top News (hero) tidak boleh di tengah:
        // bila jatuh di slot tengah, ditukar dengan artikel berikutnya.
        // Bila artikel < 4, slot diisi konten contoh sampai 4 (perilaku lama tidak berubah).
        $heroId = Article::currentHero()?->id;
        $hukumArticles = Article::query()->terbit()
            ->whereIn('category', IsfRubrik::categories('hukum'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(7)
            ->get()
            ->values();
        $centerIndex = 2;
        if ($heroId && $hukumArticles->count() > $centerIndex && $hukumArticles[$centerIndex]->id === $heroId) {
            $swapIndex = $hukumArticles->count() > $centerIndex + 1 ? $centerIndex + 1 : $centerIndex - 1;
            $all = $hukumArticles->all();
            [$all[$centerIndex], $all[$swapIndex]] = [$all[$swapIndex], $all[$centerIndex]];
            $hukumArticles = collect($all);
        }

        $hukumItems = [];
        foreach ($hukumArticles as $art) {
            $hukumItems[] = [
                'id' => (string) $art->id,
                'title' => $art->title,
                'kicker' => $art->categoryLabel(),
                'publishedAt' => $art->published_at ? $art->published_at->format('Y-m-d') : date('Y-m-d'),
                'summary' => $art->summary ?: 'Ulasan mendalam aspek yuridis, kepatuhan tata kelola, dan konsekuensi hukum bagi pemangku kepentingan.',
                'imageUrl' => $art->image_url ?: '',
                'to' => $art->publicUrl(),
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

        // 3. Kebijakan (4 Cards Layout)
        $kebijakanArticles = Article::query()->terbit()
            ->whereIn('category', IsfRubrik::categories('kebijakan'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(4)
            ->get()
            ->values();

        $kebijakanItems = [];
        foreach ($kebijakanArticles as $art) {
            $kebijakanItems[] = [
                'id' => (string) $art->id,
                'title' => $art->title,
                'kicker' => $art->categoryLabel() ?: 'KEBIJAKAN',
                'category' => 'Kebijakan',
                'summary' => $art->summary ?: '',
                'imageUrl' => $art->image_url ?: '',
                'publishedAt' => $art->published_at ? $art->published_at->format('Y-m-d') : date('Y-m-d'),
                'author' => trim(($art->author_name ?: 'Tim Riset Kebijakan ISF') . ($art->author_role ? ', ' . $art->author_role : '')),
                'to' => $art->publicUrl(),
            ];
        }

        if (count($kebijakanItems) < 4) {
            $defaultKebijakan = [
                [
                    'id' => 'kbj-1',
                    'title' => 'Kebijakan Fiskal Hijau: Skema Insentif Pajak & Transisi Energi Bersih 2026',
                    'kicker' => 'ANALISIS FISKAL',
                    'category' => 'Kebijakan',
                    'summary' => 'Pemerintah mematangkan peta jalan insentif perpajakan bagi industri pelopor energi baru terbarukan sembari mempersiapkan pemberlakuan tarif pajak karbon secara bertahap.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?q=80&w=600&auto=format&fit=crop',
                    'publishedAt' => date('Y-m-d'),
                    'author' => 'Dr. Hendra Wijaya, Dewan Pakar Fiskal ISF',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'kbj-2',
                    'title' => 'Pemetaan Dampak Regulasi Moneter & Suku Bunga Sektoral',
                    'kicker' => 'DAMPAK REGULASI',
                    'category' => 'Kebijakan',
                    'summary' => 'Likuiditas Perbankan, Sektor Riil & Manufaktur, serta iklim investasi menghadapi tren penyesuaian suku bunga acuan.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?q=80&w=600&auto=format&fit=crop',
                    'publishedAt' => date('Y-m-d', strtotime('-1 day')),
                    'author' => 'Tim Riset Kebijakan ISF',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'kbj-3',
                    'title' => 'Rekomendasi Kebijakan: Akselerasi Deregulasi Perizinan Berbasis Risiko',
                    'kicker' => 'POLICY BRIEF',
                    'category' => 'Kebijakan',
                    'summary' => 'Kepastian regulasi dan simplifikasi birokrasi digital adalah pilar fundamental untuk mendongkrak daya saing investasi Indonesia.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=600&auto=format&fit=crop',
                    'publishedAt' => date('Y-m-d', strtotime('-2 days')),
                    'author' => 'Prof. Siti Rahmawati, Ketua Tim Kajian Deregulasi ISF',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'kbj-4',
                    'title' => 'Harmonisasi Belanja Pusat & Transfer Daerah: Penguatan Fiskal 2026',
                    'kicker' => 'KEBIJAKAN FISKAL',
                    'category' => 'Kebijakan',
                    'summary' => 'Pemerintah mempercepat konsolidasi transfer ke daerah guna menjaga sinkronisasi target pembangunan nasional dan stabilitas moneter.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=600&auto=format&fit=crop',
                    'publishedAt' => date('Y-m-d', strtotime('-3 days')),
                    'author' => 'Analis Kebijakan Publik, Dewan Riset ISF',
                    'to' => '/beranda',
                ],
            ];
            foreach ($defaultKebijakan as $def) {
                if (count($kebijakanItems) < 4) {
                    $kebijakanItems[] = $def;
                }
            }
        }

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

        if ($lead = $kebijakanArticles->get(0)) {
            $kebijakanLead = [
                'id' => (string) $lead->id,
                'title' => $lead->title,
                'kicker' => $lead->categoryLabel(),
                'category' => 'Kebijakan',
                // Tanpa ringkasan → kosong (jangan campur teks contoh dengan judul artikel asli).
                'summary' => $lead->summary ?: '',
                'imageUrl' => $lead->image_url ?: $kebijakanLead['imageUrl'],
                'publishedAt' => $lead->published_at ? $lead->published_at->format('Y-m-d') : date('Y-m-d'),
                'author' => trim(($lead->author_name ?: 'Tim Riset Kebijakan ISF') . ($lead->author_role ? ', ' . $lead->author_role : '')),
                'to' => $lead->publicUrl(),
            ];
        }

        if ($impact = $kebijakanArticles->get(1)) {
            // Poin = klaim Fact Block artikel (maks. 3); tanpa Fact Block → ringkasan sebagai satu poin.
            $points = collect($impact->fact_blocks ?? [])
                ->pluck('claim')
                ->filter(fn ($claim) => is_string($claim) && trim($claim) !== '')
                ->take(3)
                ->values()
                ->all();
            if (empty($points) && $impact->summary) {
                $points = [$impact->summary];
            }

            $kebijakanImpact = [
                'title' => $impact->title,
                'kicker' => $impact->categoryLabel(),
                'publishedAt' => $impact->published_at ? $impact->published_at->format('Y-m-d') : date('Y-m-d'),
                'keyPoints' => $points,
                'imageUrl' => $impact->image_url ?: '',
                'to' => $impact->publicUrl(),
            ];
        }

        if ($brief = $kebijakanArticles->get(2)) {
            $kebijakanBrief = [
                'title' => $brief->title,
                'kicker' => $brief->categoryLabel(),
                'publishedAt' => $brief->published_at ? $brief->published_at->format('Y-m-d') : date('Y-m-d'),
                'author' => $brief->author_name ?: 'Tim Riset Kebijakan ISF',
                'authorRole' => $brief->author_role ?: '',
                // Artikel belum punya field kutipan: ringkasan dipakai sebagai kutipan Policy Brief.
                'quote' => $brief->summary ?: '',
                'imageUrl' => $brief->image_url ?: '',
                'to' => $brief->publicUrl(),
            ];
        }

        // 4. Video (Multimedia Hub)
        $videoFeatured = null;
        $videoPlaylist = [];

        if (!empty($activeMedia)) {
            $firstMedia = $activeMedia[0];
            $videoFeatured = [
                'id' => $firstMedia['id'] ?? 1,
                'title' => $firstMedia['title'] ?? 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi',
                'summary' => $firstMedia['summary'] ?? 'Kajian mendalam pakar regulator dan praktisi terkait kepatuhan tata kelola digital.',
                // Label & durasi dari CMS Media Sorotan; kosong → nilai lama.
                'category' => ! empty($firstMedia['label']) ? $firstMedia['label'] : 'Diskusi Strategis',
                'duration' => ! empty($firstMedia['duration']) ? $firstMedia['duration'] : '18:45',
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
                    // Label & durasi dari CMS Media Sorotan; kosong → nilai lama (platform / durasi contoh).
                    'category' => ! empty($m['label']) ? $m['label'] : ($m['category'] ?? 'Video Analisis'),
                    'duration' => ! empty($m['duration']) ? $m['duration'] : ($idx === 0 ? '08:20' : ($idx === 1 ? '12:15' : '05:40')),
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
                'items' => $kebijakanItems,
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

    public function survei(Request $request): JsonResponse
    {
        $topic = $request->query('isu') ?? $request->query('topic');
        $region = $request->query('wilayah') ?? $request->query('region');
        $q = $request->query('q');
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(50, (int) $request->query('limit', 9)));

        $query = Survey::terbit();

        if ($topic && $topic !== 'semua') {
            $query->where('topic', strtolower($topic));
        }

        if ($region && $region !== 'semua') {
            $query->where('region', strtolower($region));
        }

        if ($q && trim($q) !== '') {
            $term = '%' . trim($q) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('title', 'like', $term)
                    ->orWhere('institution', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('headline_stat_label', 'like', $term);
            });
        }

        // Featured Survey for hero highlight
        $featuredSurvey = Survey::terbit()->where('is_featured', true)->first()
            ?? Survey::terbit()->orderBy('release_date', 'desc')->first();

        $total = (clone $query)->count();
        $surveys = $query->orderBy('order', 'asc')
            ->orderBy('release_date', 'desc')
            ->orderBy('id', 'desc')
            ->forPage($page, $limit)
            ->get();

        $lastPage = max(1, (int) ceil($total / $limit));

        return response()->json([
            'rubrik' => [
                'key' => 'survei',
                'title' => 'Survei',
                'label' => 'Survei',
                'description' => 'Dashboard rilis survei opini publik, metodologi riset terpercaya, agregasi persepsi nasional, dan visualisasi data interaktif.',
                'topics' => [
                    ['slug' => 'semua', 'name' => 'Semua Isu'],
                    ['slug' => 'politik', 'name' => 'Politik'],
                    ['slug' => 'ekonomi', 'name' => 'Ekonomi'],
                    ['slug' => 'sosial', 'name' => 'Sosial'],
                    ['slug' => 'hukum', 'name' => 'Hukum'],
                ],
                'regions' => [
                    ['slug' => 'semua', 'name' => 'Semua Wilayah'],
                    ['slug' => 'nasional', 'name' => 'Nasional'],
                    ['slug' => 'jawa', 'name' => 'Pulau Jawa'],
                    ['slug' => 'sumatera', 'name' => 'Sumatera'],
                    ['slug' => 'kalimantan', 'name' => 'Kalimantan'],
                    ['slug' => 'sulawesi', 'name' => 'Sulawesi'],
                    ['slug' => 'bali_nusra', 'name' => 'Bali & Nusa Tenggara'],
                    ['slug' => 'maluku_papua', 'name' => 'Maluku & Papua'],
                ],
                'activeTopic' => $topic ?: 'semua',
                'activeRegion' => $region ?: 'semua',
            ],
            'featuredSurvey' => $featuredSurvey,
            'surveys' => $surveys,
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
        ]);
    }

    public function surveiDetail(string $slug): JsonResponse
    {
        $survey = Survey::terbit()->where('slug', $slug)->first();
        if (! $survey) {
            return response()->json(['message' => 'Laporan survei tidak ditemukan.'], 404);
        }

        $relatedSurveys = Survey::terbit()
            ->where('id', '!=', $survey->id)
            ->where(function ($q) use ($survey) {
                $q->where('topic', $survey->topic)
                  ->orWhere('region', $survey->region);
            })
            ->orderBy('release_date', 'desc')
            ->limit(3)
            ->get();

        if ($relatedSurveys->isEmpty()) {
            $relatedSurveys = Survey::terbit()
                ->where('id', '!=', $survey->id)
                ->orderBy('release_date', 'desc')
                ->limit(3)
                ->get();
        }

        return response()->json([
            'survey' => $survey,
            'relatedSurveys' => $relatedSurveys,
        ]);
    }

    /**
     * Map model Tokoh ke kartu representasi Sosok publik.
     */
    private function sosokCardMapper(): \Closure
    {
        return fn(Tokoh $t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'position' => $t->position,
            'role_category' => $t->role_category ?? 'legislatif',
            'role_category_label' => Tokoh::ROLE_CATEGORIES[$t->role_category] ?? ucfirst($t->role_category ?? 'Tokoh'),
            'issues' => $t->issues ?? [],
            'summary_analysis' => $t->summary_analysis ?? $t->teaser ?? '',
            'bio' => $t->bio ?? $t->teaser ?? '',
            'photo_url' => $t->photo_url,
            'photo_alt' => $t->photo_alt ?: "Foto profil {$t->name}",
            'influence_score' => $t->influence_score ?? $t->skor_value ?? 75,
            'skor_value' => $t->skor_value ?? 75,
            'rating_average' => $t->rating_average !== null ? round((float) $t->rating_average, 1) : null,
            'rating_count' => (int) ($t->rating_count ?? 0),
            'is_influential_weekly' => (bool) ($t->is_influential_weekly || $t->is_featured),
            'to' => "/sosok/{$t->slug}",
        ];
    }

    /**
     * GET /api/isf/sosok
     *
     * Halaman Indeks & Subkategori Rubrik Sosok (/sosok dan /sosok/[kategori]):
     * - Hero: Tokoh Paling Berpengaruh Minggu Ini
     * - Filter: Kategori (eksekutif, legislatif, dll.), Isu, dan Alfabet A-Z
     * - Grid profil tokoh publik
     */
    public function sosok(Request $request): JsonResponse
    {
        $kategori = $request->query('kategori') ?? $request->query('category');
        $isu = $request->query('isu') ?? $request->query('issue');
        $abjad = $request->query('abjad') ?? $request->query('alphabet');
        $q = $request->query('q');
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(50, (int) $request->query('limit', 12)));

        $query = Tokoh::query()->terbit();

        if ($kategori && $kategori !== 'semua') {
            $query->roleCategory($kategori);
        }

        if ($isu && $isu !== 'semua') {
            $query->issue($isu);
        }

        if ($abjad && $abjad !== 'semua') {
            $query->alphabet($abjad);
        }

        if ($q && trim($q) !== '') {
            $term = '%' . trim($q) . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('position', 'like', $term)
                    ->orWhere('summary_analysis', 'like', $term)
                    ->orWhere('bio', 'like', $term);
            });
        }

        // Hero: Tokoh Paling Berpengaruh Minggu Ini
        $featuredHero = Tokoh::currentFeatured();
        if ($featuredHero) {
            $featuredHero->loadAvg('reviews as rating_average', 'rating');
            $featuredHero->loadCount('reviews as rating_count');
        }

        $total = (clone $query)->count();
        $items = $query->withAvg('reviews as rating_average', 'rating')
            ->withCount('reviews as rating_count')
            ->orderBy('name', 'asc')
            ->forPage($page, $limit)
            ->get();

        $lastPage = max(1, (int) ceil($total / $limit));
        $cardMapper = $this->sosokCardMapper();

        return response()->json([
            'rubrik' => [
                'key' => 'sosok',
                'title' => 'Sosok',
                'label' => 'Sosok',
                'description' => 'Direktori komprehensif profil tokoh nasional, rekam jejak, skor pengaruh, dan pemetaan jejaring strategis.',
                'kategoriOptions' => [
                    ['slug' => 'semua', 'name' => 'Semua Tokoh'],
                    ['slug' => 'eksekutif', 'name' => 'Eksekutif'],
                    ['slug' => 'legislatif', 'name' => 'Legislatif'],
                    ['slug' => 'pakar', 'name' => 'Pakar & Akademisi'],
                ],
                'activeKategori' => $kategori ?: 'semua',
                'isuOptions' => [
                    ['slug' => 'semua', 'name' => 'Semua Isu'],
                    ['slug' => 'politik', 'name' => 'Politik'],
                    ['slug' => 'hukum', 'name' => 'Hukum'],
                    ['slug' => 'ekonomi', 'name' => 'Ekonomi'],
                    ['slug' => 'pertahanan', 'name' => 'Pertahanan'],
                    ['slug' => 'kebijakan', 'name' => 'Kebijakan'],
                ],
                'activeIsu' => $isu ?: 'semua',
                'activeAbjad' => $abjad ?: 'semua',
            ],
            'hero' => $featuredHero ? $cardMapper($featuredHero) : null,
            'items' => $items->map($cardMapper)->values()->all(),
            'meta' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
                'total' => $total,
                'perPage' => $limit,
            ],
        ]);
    }

    /**
     * GET /api/isf/sosok/{slug}
     *
     * Halaman Detail Tokoh dengan 5 Tab:
     * - Tab 1: Profil & Rekam Jejak (/profil)
     * - Tab 2: Analisis ISF (/analisis): skor pengaruh, policy brief, rating & ulasan (#41), jejak kebijakan
     * - Tab 3: Jejaring & Afiliasi (/jejaring): visualisasi relasi, partai/organisasi, sekutu & oposan
     * - Tab 4: Berita & Aktivitas (/berita): feed otomatis artikel yang menyebut tokoh/alias
     * - Tab 5: Kutipan & Kontroversi (/kontroversi): kumpulan kutipan penting & timeline kontroversi
     */
    public function sosokDetail(Request $request, string $slug): JsonResponse
    {
        $tokoh = Tokoh::query()->terbit()->where('slug', $slug)->first();
        if (! $tokoh) {
            return $this->notFound('Tokoh tidak ditemukan.');
        }

        $cardMapper = $this->sosokCardMapper();

        // 1. Tab Profil
        $profil = [
            'bio' => $tokoh->bio ?: $tokoh->teaser ?: '',
            'career_timeline' => $tokoh->career_timeline ?? [],
            'education' => $tokoh->education ?? [],
            'positions_history' => $tokoh->positions_history ?? [],
            'stats' => $tokoh->stats ?? [],
            'affiliations' => $tokoh->affiliations ?? [],
        ];

        // 2. Tab Analisis ISF
        $issues = (array) ($tokoh->issues ?? []);
        $policyBriefs = PolicyBrief::query()->terbit()
            ->where(function ($q) use ($issues) {
                if (!empty($issues)) {
                    $q->whereIn('sector', $issues);
                }
            })
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        // Review summary (#41)
        $reviewQuery = TokohReview::query()->where('tokoh_id', $tokoh->id);
        $reviewCount = (clone $reviewQuery)->count();
        $reviewAvg = $reviewCount > 0 ? round((clone $reviewQuery)->avg('rating'), 1) : 0.0;

        $analisis = [
            'influence_score' => $tokoh->influence_score ?? $tokoh->skor_value ?? 75,
            'skorSorotan' => [
                'value' => $tokoh->skor_value,
                'period' => $tokoh->skor_period,
                'explanation' => $tokoh->skor_explanation,
            ],
            'policyMatrix' => [
                'columns' => $tokoh->matrix_columns ?? [],
                'rows' => $tokoh->matrix_rows ?? [],
            ],
            'policyBriefs' => $policyBriefs,
            'ratingSummary' => [
                'average' => $reviewAvg,
                'total' => $reviewCount,
            ],
        ];

        // 3. Tab Jejaring & Afiliasi
        $jejaring = [
            'affiliations' => $tokoh->affiliations ?? [],
            'allies' => $tokoh->allies ?? [],
            'critics' => $tokoh->critics ?? [],
        ];

        // 4. Tab Berita & Aktivitas (otomatis dari artikel yang menyebut nama/alias)
        $aliases = array_filter(array_merge([$tokoh->name], (array) ($tokoh->aliases ?? [])));
        $articleQuery = Article::query()->terbit();
        $articleQuery->where(function ($q) use ($aliases, $tokoh) {
            foreach ($aliases as $alias) {
                if (trim($alias) !== '') {
                    $term = '%' . trim($alias) . '%';
                    $q->orWhere('title', 'like', $term)
                      ->orWhere('summary', 'like', $term)
                      ->orWhere('body', 'like', $term);
                }
            }
            if (!empty($tokoh->related_articles)) {
                foreach ($tokoh->related_articles as $ra) {
                    if (!empty($ra['title'])) {
                        $q->orWhere('title', 'like', '%' . trim($ra['title']) . '%');
                    }
                }
            }
        });
        $feedArticles = $articleQuery->orderByDesc('published_at')->limit(8)->get();
        $articleCard = $this->articleCardMapper($this->categoryLabeler());

        $berita = [
            'articles' => $feedArticles->map($articleCard)->values()->all(),
        ];

        // 5. Tab Kutipan & Kontroversi
        $quotes = $tokoh->quotes ?? [];
        $controversies = $tokoh->controversies ?? [];
        $publicQuotes = PublicQuote::query()->terbit()
            ->where('author_name', 'like', "%{$tokoh->name}%")
            ->limit(5)
            ->get();

        $kontroversi = [
            'quotes' => $quotes,
            'controversies' => $controversies,
            'publicQuotes' => $publicQuotes,
        ];

        // Related Tokoh: serumpun & terbagi menjadi Tokoh Eksekutif dan Tokoh Legislatif
        $related = Tokoh::query()->terbit()
            ->withAvg('reviews as rating_average', 'rating')
            ->withCount('reviews as rating_count')
            ->where('id', '!=', $tokoh->id)
            ->where('role_category', $tokoh->role_category)
            ->limit(4)
            ->get();

        if ($related->isEmpty()) {
            $related = Tokoh::query()->terbit()
                ->withAvg('reviews as rating_average', 'rating')
                ->withCount('reviews as rating_count')
                ->where('id', '!=', $tokoh->id)
                ->limit(4)
                ->get();
        }

        // Tokoh Eksekutif (Pemerintahan / Kementerian / Presiden)
        $tokohEksekutif = Tokoh::query()->terbit()
            ->withAvg('reviews as rating_average', 'rating')
            ->withCount('reviews as rating_count')
            ->where('id', '!=', $tokoh->id)
            ->where(function ($q) {
                $q->where('role_category', 'eksekutif')
                  ->orWhere('position', 'like', '%menteri%')
                  ->orWhere('position', 'like', '%presiden%')
                  ->orWhere('position', 'like', '%gubernur%');
            })
            ->limit(4)
            ->get();

        if ($tokohEksekutif->isEmpty()) {
            $tokohEksekutif = Tokoh::query()->terbit()
                ->withAvg('reviews as rating_average', 'rating')
                ->withCount('reviews as rating_count')
                ->where('id', '!=', $tokoh->id)
                ->limit(4)
                ->get();
        }

        // Tokoh Legislatif (Parlemen DPR / DPD / MPR)
        $tokohLegislatif = Tokoh::query()->terbit()
            ->withAvg('reviews as rating_average', 'rating')
            ->withCount('reviews as rating_count')
            ->where('id', '!=', $tokoh->id)
            ->where(function ($q) {
                $q->where('role_category', 'legislatif')
                  ->orWhere('position', 'like', '%dpr%')
                  ->orWhere('position', 'like', '%dpd%')
                  ->orWhere('position', 'like', '%mpr%')
                  ->orWhere('position', 'like', '%komisi%');
            })
            ->limit(4)
            ->get();

        if ($tokohLegislatif->isEmpty()) {
            $tokohLegislatif = Tokoh::query()->terbit()
                ->withAvg('reviews as rating_average', 'rating')
                ->withCount('reviews as rating_count')
                ->where('id', '!=', $tokoh->id)
                ->whereNotIn('id', $tokohEksekutif->pluck('id')->all())
                ->limit(4)
                ->get();

            if ($tokohLegislatif->isEmpty()) {
                $tokohLegislatif = $tokohEksekutif;
            }
        }

        $tokohData = $cardMapper($tokoh);
        $tokohData['rating_average'] = $reviewAvg;
        $tokohData['rating_count'] = $reviewCount;

        return response()->json([
            'tokoh' => $tokohData,
            'tabs' => [
                'profil' => $profil,
                'analisis' => $analisis,
                'jejaring' => $jejaring,
                'berita' => $berita,
                'kontroversi' => $kontroversi,
            ],
            'calendar' => [
                'year' => $tokoh->calendar_year,
                'month' => $tokoh->calendar_month,
                'events' => $tokoh->calendar_events ?? [],
            ],
            'relatedTokoh' => $related->map($cardMapper)->values()->all(),
            'tokohEksekutif' => $tokohEksekutif->map($cardMapper)->values()->all(),
            'tokohLegislatif' => $tokohLegislatif->map($cardMapper)->values()->all(),
        ]);
    }

    /**
     * GET /api/isf/media-kit
     *
     * Halaman Publik Informasi Iklan & Kemitraan (Media Kit):
     * - Pitch audiens (C-level, ASN, Akademisi, Praktisi Hukum)
     * - Metrik & performa jangkauan
     * - Format & kanal kemitraan (Display Banner, Advertorial, Event/Diskusi, Riset & Policy Brief)
     * - FAQ kemitraan
     * - Dukungan kustomisasi melalui Setting::get('media_kit')
     */
    public function mediaKit(?Request $request = null): JsonResponse
    {
        $cmsSettings = Setting::get('media_kit', []);

        $pitch = $cmsSettings['pitch'] ?? [
            'badge' => 'Informasi Iklan & Kemitraan',
            'title' => 'Menghubungkan Gagasan, Kebijakan, dan Pengambil Keputusan Nasional',
            'deck' => 'Indonesia Strategy Forum (ISF) adalah kanal referensi independen untuk para pembuat kebijakan, eksekutif puncak korporasi, akademisi, dan profesional hukum yang membentuk arah pembangunan Indonesia.',
        ];

        $audiens = $cmsSettings['audiens'] ?? [
            'total_monthly_readers' => '150.000+',
            'total_monthly_readers_label' => 'Pembaca Terverifikasi / Bulan',
            'executive_share' => '45.000+',
            'executive_share_label' => 'Eksekutif & C-Suite',
            'retention_rate' => '85%',
            'retention_rate_label' => 'Tingkat Retensi Pembaca',
            'segments' => [
                [
                    'name' => 'C-Suite, Direksi & Pengambil Keputusan Bisnis',
                    'percentage' => 34,
                    'color' => '#E10600',
                    'desc' => 'CEO, CFO, General Counsel, dan pimpinan korporasi swasta serta BUMN.',
                ],
                [
                    'name' => 'ASN, Regulator & Pembuat Kebijakan Publik',
                    'percentage' => 28,
                    'color' => '#2563EB',
                    'desc' => 'Pejabat kementerian, pimpinan komisi DPR/DPD, staf khusus, dan lembaga non-struktural.',
                ],
                [
                    'name' => 'Akademisi, Peneliti & Analis Kebijakan',
                    'percentage' => 22,
                    'color' => '#059669',
                    'desc' => 'Guru besar, peneliti think tank kebijakan, analis makroekonomi, dan pakar riset.',
                ],
                [
                    'name' => 'Praktisi Hukum, Konsultan & Advokat',
                    'percentage' => 16,
                    'color' => '#D97706',
                    'desc' => 'Managing partner law firm, konsultan kepatuhan regulasi, dan auditor independen.',
                ],
            ],
        ];

        $channels = $cmsSettings['channels'] ?? [
            [
                'id' => 'banner',
                'title' => 'Display Banner & Slot Iklan Strategis',
                'kicker' => 'VISIBILITAS TERTARGET',
                'desc' => 'Penempatan visual terkurasi tanpa gangguan clutter: Banner Beranda, Billboard Rubrik Utama (Politik, Hukum, Kebijakan), dan In-Article Sidebar Banner.',
                'highlights' => [
                    '100% Brand-Safe Environment bebas sensasionalisme',
                    'Slot terbatas untuk mempertahankan estetika editorial tinggi',
                    'Pelaporan performa impresi dan interaksi berkala',
                ],
                'tag' => 'Display Banner',
            ],
            [
                'id' => 'advertorial',
                'title' => 'Advertorial & Analisis Kebijakan Khusus',
                'kicker' => 'THOUGHT LEADERSHIP',
                'desc' => 'Kajian komprehensif, wawancara kepemimpinan, dan laporan sektoral mendalam bertanda Transparansi Kemitraan dengan standar jurnalisme bermutu tinggi.',
                'highlights' => [
                    'Disusun bersama tim riset ISF dan pakar terkemuka',
                    'Didistribusikan ke jejaring buletin eksekutif mingguan',
                    'Kanalisasi opini di rubrik Opini Publik & Kebijakan',
                ],
                'tag' => 'Advertorial',
            ],
            [
                'id' => 'event',
                'title' => 'Event & Roundtable Diskusi Kebijakan',
                'kicker' => 'DIALOG LANGSUNG',
                'desc' => 'Penyelenggaraan roundtable tertutup (Chatham House Rule) atau seminar eksekutif yang mempertemukan regulator kementerian, pimpinan BUMN, dan asosiasi industri.',
                'highlights' => [
                    'Fasilitasi moderator Dewan Pakar ISF',
                    'Peserta eksklusif setingkat pengambil keputusan',
                    'Penyusunan naskah rekomendasi hasil diskusi',
                ],
                'tag' => 'Event & Forum',
            ],
            [
                'id' => 'riset',
                'title' => 'Sponsorship Riset & Policy Brief',
                'kicker' => 'EVIDENCE-BASED POLICY',
                'desc' => 'Kolaborasi penyusunan naskah ringkasan kebijakan (Policy Brief) dan visualisasi infografik data yang dapat diunduh oleh ribuan pembaca terdaftar.',
                'highlights' => [
                    'Penyematan identitas mitra resmi pada publikasi dokumen PDF',
                    'Distribusi khusus ke jejaring lembaga negara dan universitas',
                    'Hak diseminasi riset untuk pemangku kepentingan strategis',
                ],
                'tag' => 'Riset & Brief',
            ],
        ];

        $workflow = [
            [
                'step' => '01',
                'title' => 'Pengajuan Kemitraan (RFP)',
                'desc' => 'Klien atau lembaga mengisi formulir Request for Proposal di bawah ini dengan melampirkan objektif dan jenis kemitraan yang diminati.',
            ],
            [
                'step' => '02',
                'title' => 'Konsultasi & Penyelarasan (1x24 Jam)',
                'desc' => 'Tim Kemitraan ISF akan menghubungi PIC dalam 1x24 jam kerja untuk menyelaraskan kebutuhan, sudut pandang konten, dan target audiens.',
            ],
            [
                'step' => '03',
                'title' => 'Penyusunan Proposal Khusus',
                'desc' => 'ISF menyusun proposal kerjasama yang disesuaikan secara terukur (tailor-made) mencakup jadwal publikasi, format materi, dan parameter keberhasilan.',
            ],
            [
                'step' => '04',
                'title' => 'Eksekusi & Laporan Dampak',
                'desc' => 'Materi dipublikasikan atau event dijalankan sesuai standar editorial ISF, disertai laporan dampak distribusi kepada mitra.',
            ],
        ];

        $faq = [
            [
                'q' => 'Mengapa ISF tidak menampilkan daftar harga (Rate Card) di website?',
                'a' => 'ISF tidak menggunakan tarif baku satu harga karena setiap kolaborasi dirancang secara tailor-made berdasarkan format kampanye, kedalaman riset, dan tujuan strategis mitra. Pendekatan ini memastikan setiap investasi kemitraan memberikan dampak maksimal.',
            ],
            [
                'q' => 'Berapa lama formulir RFP ditindaklanjuti?',
                'a' => 'Tim Kemitraan ISF berkomitmen menghubungi Anda dalam kurun waktu 1x24 jam kerja setelah formulir dikirimkan.',
            ],
            [
                'q' => 'Apakah instansi pemerintah, BUMN, dan asosiasi diperbolehkan bermitra?',
                'a' => 'Ya, ISF bermitra aktif dengan kementerian, lembaga negara, BUMN, asosiasi industri, institusi akademik, dan korporasi swasta untuk diseminasi wawasan kebijakan yang bermanfaat bagi publik.',
            ],
            [
                'q' => 'Bagaimana pedoman etika editorial untuk konten berbayar?',
                'a' => 'ISF memegang teguh independensi editorial. Seluruh konten berbayar diberi penanda transparan seperti "Kemitraan Strategis" atau "Advertorial", dan wajib memenuhi standar akurasi serta bebas dari disinformasi.',
            ],
        ];

        return response()->json([
            'pitch' => $pitch,
            'audiens' => $audiens,
            'channels' => $channels,
            'workflow' => $workflow,
            'faq' => $faq,
        ]);
    }

    /**
     * POST /api/isf/media-kit/rfp
     *
     * Kirim formulir Request for Proposal (RFP) kemitraan publik:
     * - Validasi field & visual captcha
     * - Simpan ke isf_partnership_proposals
     * - Kirim auto-reply email ke klien via queue
     */
    public function submitPartnershipProposal(PartnershipProposalRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $proposal = PartnershipProposal::create([
            'institution_name' => $validated['institution_name'],
            'pic_name' => $validated['pic_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'partnership_type' => $validated['partnership_type'],
            'message' => $validated['message'],
            'status' => PartnershipProposal::STATUS_BARU,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        // Kirim email auto-reply via queue (kegagalan email tidak menggagalkan response)
        try {
            Mail::to($proposal->email)->queue(new PartnershipAutoReplyMail($proposal));
        } catch (\Throwable $e) {
            Log::warning('Gagal mengantrekan email auto-reply kemitraan: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Permintaan kemitraan Anda berhasil dikirim. Tim Kemitraan ISF akan menghubungi Anda dalam kurun waktu 1x24 jam kerja.',
            'data' => [
                'id' => $proposal->id,
                'institution_name' => $proposal->institution_name,
                'pic_name' => $proposal->pic_name,
                'partnership_type' => $proposal->partnership_type,
                'partnership_type_label' => $proposal->partnership_type_label,
                'status' => $proposal->status,
                'created_at' => $proposal->created_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    /**
     * Konten dinamis Footer untuk landing page dan situs publik (Issue #99).
     */
    public function footer(): JsonResponse
    {
        $footer = Setting::get('footer', FooterSettingController::defaultFooter());
        $merged = array_merge(FooterSettingController::defaultFooter(), $footer);

        return response()->json([
            'data' => $merged,
        ]);
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }
}
